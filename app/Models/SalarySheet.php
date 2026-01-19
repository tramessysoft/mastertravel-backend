<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalarySheet extends Model
{
    protected $guarded = [];

   
    
    public function items()
    {
        // Correct model class name
        return $this->hasMany(Salary_items::class, 'salary_id');
    }
    
    
}
