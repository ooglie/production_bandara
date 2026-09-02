<?php

namespace App\Services\Finance;

use App\Models\BusinessExpense;
use App\Models\RecurringExpenseTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecurringExpenseGenerator
{
    public function __construct(private readonly ExpenseNumberService $numbers)
    {
    }

    /**
     * @return array{created:int, skipped:int, errors:array<int, string>}
     */
    public function generateDue(
        CarbonInterface|string|null $throughDate = null,
        ?int $createdById = null,
    ): array {
        $through = $throughDate === null
            ? CarbonImmutable::today()
            : CarbonImmutable::parse($throughDate)->startOfDay();

        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];

        $templateIds = RecurringExpenseTemplate::query()
            ->dueOnOrBefore($through)
            ->orderBy('next_due_date')
            ->pluck('id');

        foreach ($templateIds as $templateId) {
            try {
                $counts = DB::transaction(function () use ($templateId, $through, $createdById): array {
                    $counts = ['created' => 0, 'skipped' => 0];

                    /** @var RecurringExpenseTemplate|null $template */
                    $template = RecurringExpenseTemplate::query()
                        ->with('category')
                        ->lockForUpdate()
                        ->find($templateId);

                    if ($template === null || ! $template->is_active || $template->category === null) {
                        $counts['skipped']++;

                        return $counts;
                    }

                    $due = CarbonImmutable::parse($template->next_due_date)->startOfDay();
                    $safety = 0;

                    while ($template->is_active && $due->lessThanOrEqualTo($through)) {
                        $safety++;

                        if ($safety > 120) {
                            throw new \RuntimeException('Recurring generation safety limit reached. Check the template frequency and next due date.');
                        }

                        if ($template->end_date !== null && $due->greaterThan($template->end_date)) {
                            $template->is_active = false;
                            $template->save();
                            break;
                        }

                        $existingExpense = BusinessExpense::withTrashed()
                            ->where('recurring_expense_template_id', $template->id)
                            ->whereDate('generated_for_date', $due->toDateString())
                            ->first();

                        if ($existingExpense !== null) {
                            $counts['skipped']++;
                        } else {
                            BusinessExpense::query()->create([
                                'expense_number' => $this->numbers->generate($due),
                                'expense_date' => $due->toDateString(),
                                'expense_category_id' => $template->expense_category_id,
                                'description' => $template->description,
                                'payee' => $template->payee,
                                'taxable_amount' => $template->expected_taxable_amount,
                                'gst_amount' => $template->expected_gst_amount,
                                'total_amount' => $template->expected_total_amount,
                                'record_status' => BusinessExpense::STATUS_DRAFT,
                                'payment_status' => BusinessExpense::PAYMENT_UNPAID,
                                'payment_method' => $template->default_payment_method,
                                'due_date' => $due->toDateString(),
                                'notes' => $template->notes,
                                'recurring_expense_template_id' => $template->id,
                                'generated_for_date' => $due->toDateString(),
                                'created_by_id' => $createdById,
                                'updated_by_id' => $createdById,
                            ]);
                            $counts['created']++;
                        }

                        $nextDue = $template->nextDateAfter($due);

                        if ($nextDue->lessThanOrEqualTo($due)) {
                            throw new \RuntimeException('Recurring template did not advance to a later due date.');
                        }

                        $template->next_due_date = $nextDue->toDateString();

                        if ($template->end_date !== null && $nextDue->greaterThan($template->end_date)) {
                            $template->is_active = false;
                        }

                        $template->updated_by_id = $createdById;
                        $template->save();
                        $due = $nextDue;
                    }

                    return $counts;
                }, 3);

                $result['created'] += $counts['created'];
                $result['skipped'] += $counts['skipped'];
            } catch (Throwable $exception) {
                report($exception);
                $result['errors'][] = "Template {$templateId}: {$exception->getMessage()}";
            }
        }

        return $result;
    }
}
