<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionRun extends Model
{
    protected $fillable = [
        'run_number',
        'run_date',
        'run_type',
        'status',
        'process_flow_json',
        'notes',
        'input_weight_kg',
        'saleable_output_weight_kg',
        'trim_weight_kg',
        'waste_weight_kg',
        'yield_percent',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'run_date' => 'date',
        'process_flow_json' => 'array',
        'input_weight_kg' => 'decimal:3',
        'saleable_output_weight_kg' => 'decimal:3',
        'trim_weight_kg' => 'decimal:3',
        'waste_weight_kg' => 'decimal:3',
        'yield_percent' => 'decimal:2',
    ];

    public function inputs()
    {
        return $this->hasMany(ProductionRunInput::class);
    }

    public function outputs()
    {
        return $this->hasMany(ProductionRunOutput::class);
    }

    public function outputLots()
    {
        return $this->hasMany(InventoryLot::class, 'production_run_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}