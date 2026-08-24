<?php

declare(strict_types=1);

namespace App\Enums;

enum B2BApplicationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case MoreInformationRequired = 'more_information_required';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Application received',
            self::UnderReview => 'Under review',
            self::MoreInformationRequired => 'Additional information required',
            self::Approved => 'Business account approved',
            self::Rejected => 'Application could not be approved',
            self::Withdrawn => 'Application withdrawn',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
            self::Submitted => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950/50 dark:text-sky-200 dark:ring-sky-800',
            self::UnderReview => 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-200 dark:ring-amber-800',
            self::MoreInformationRequired => 'bg-orange-50 text-orange-800 ring-orange-200 dark:bg-orange-950/50 dark:text-orange-200 dark:ring-orange-800',
            self::Approved => 'bg-emerald-50 text-emerald-800 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-200 dark:ring-emerald-800',
            self::Rejected => 'bg-rose-50 text-rose-800 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-200 dark:ring-rose-800',
            self::Withdrawn => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
        };
    }

    public function customerCanEdit(): bool
    {
        return in_array($this, [self::Draft, self::MoreInformationRequired], true);
    }

    public function customerCanSubmit(): bool
    {
        return $this->customerCanEdit();
    }

    public function customerCanWithdraw(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::UnderReview, self::MoreInformationRequired], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::Submitted, self::UnderReview, self::MoreInformationRequired], true);
    }
}
