<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryChargeRule;
use App\Models\DeliveryZone;
use App\Models\DeliveryZonePincode;
use App\Models\HandlingChargeRule;
use Illuminate\Http\Request;

class DeliverySettingsController extends Controller
{
    public function index()
    {
        return view('admin.delivery.index', [
            'zones' => DeliveryZone::query()
                ->with(['pincodes' => fn ($query) => $query->orderBy('pincode'), 'deliveryChargeRules' => fn ($query) => $query->orderBy('customer_type')->orderBy('min_order_value')])
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(),
            'handlingRules' => HandlingChargeRule::query()
                ->orderBy('customer_type')
                ->orderBy('temperature_mode')
                ->orderBy('min_order_value')
                ->get(),
        ]);
    }

    public function storeZone(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'unique:delivery_zones,code'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        DeliveryZone::create($data);

        return back()->with('success', 'Delivery zone created.');
    }

    public function updateZone(Request $request, DeliveryZone $zone)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:80', 'unique:delivery_zones,code,' . $zone->id],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper(trim($data['code']));
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        $zone->update($data);

        return back()->with('success', 'Delivery zone updated.');
    }

    public function storePincode(Request $request, DeliveryZone $zone)
    {
        $data = $request->validate([
            'pincode' => ['required', 'string', 'max:10', 'unique:delivery_zone_pincodes,pincode'],
            'city' => ['nullable', 'string', 'max:120'],
            'area_name' => ['nullable', 'string', 'max:160'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['pincode'] = preg_replace('/\D+/', '', $data['pincode']);
        $data['is_active'] = $request->boolean('is_active', true);

        $zone->pincodes()->create($data);

        return back()->with('success', 'Pincode added to delivery zone.');
    }

    public function destroyPincode(DeliveryZonePincode $pincode)
    {
        $pincode->delete();

        return back()->with('success', 'Pincode removed.');
    }

    public function storeDeliveryRule(Request $request, DeliveryZone $zone)
    {
        $data = $request->validate([
            'customer_type' => ['required', 'in:all,guest,b2c,b2b'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_above' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = $this->normalizeMoneyRule($request, $data, 'delivery_fee', 'free_delivery_above');
        $zone->deliveryChargeRules()->create($data);

        return back()->with('success', 'Delivery fee rule added.');
    }

    public function updateDeliveryRule(Request $request, DeliveryChargeRule $rule)
    {
        $data = $request->validate([
            'customer_type' => ['required', 'in:all,guest,b2c,b2b'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_above' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = $this->normalizeMoneyRule($request, $data, 'delivery_fee', 'free_delivery_above');
        $rule->update($data);

        return back()->with('success', 'Delivery fee rule updated.');
    }

    public function destroyDeliveryRule(DeliveryChargeRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Delivery fee rule removed.');
    }

    public function storeHandlingRule(Request $request)
    {
        $data = $request->validate([
            'customer_type' => ['required', 'in:all,guest,b2c,b2b'],
            'temperature_mode' => ['required', 'in:all,frozen,chilled,ambient'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'handling_fee' => ['nullable', 'numeric', 'min:0'],
            'free_handling_above' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = $this->normalizeMoneyRule($request, $data, 'handling_fee', 'free_handling_above');
        HandlingChargeRule::create($data);

        return back()->with('success', 'Handling fee rule added.');
    }

    public function updateHandlingRule(Request $request, HandlingChargeRule $rule)
    {
        $data = $request->validate([
            'customer_type' => ['required', 'in:all,guest,b2c,b2b'],
            'temperature_mode' => ['required', 'in:all,frozen,chilled,ambient'],
            'min_order_value' => ['nullable', 'numeric', 'min:0'],
            'handling_fee' => ['nullable', 'numeric', 'min:0'],
            'free_handling_above' => ['nullable', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = $this->normalizeMoneyRule($request, $data, 'handling_fee', 'free_handling_above');
        $rule->update($data);

        return back()->with('success', 'Handling fee rule updated.');
    }

    public function destroyHandlingRule(HandlingChargeRule $rule)
    {
        $rule->delete();

        return back()->with('success', 'Handling fee rule removed.');
    }

    private function normalizeMoneyRule(Request $request, array $data, string $feeField, string $freeAboveField): array
    {
        $data['min_order_value'] = round((float) ($data['min_order_value'] ?? 0), 2);
        $data[$feeField] = round((float) ($data[$feeField] ?? 0), 2);
        $data[$freeAboveField] = $request->filled($freeAboveField) ? round((float) $data[$freeAboveField], 2) : null;
        $data['tax_rate'] = round((float) ($data['tax_rate'] ?? 0), 2);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
