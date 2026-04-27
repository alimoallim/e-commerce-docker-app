<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'total',
        'date',
         'payment_status' 
    ];

    // ✅ IMPORTANT: expose computed fields
    protected $appends = [
        'paid_amount',
        'remaining_amount'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    //  Always compute directly from DB
    public function getPaidAmountAttribute()
    {
        return (float) $this->payments()->sum('amount');
    }

    //  Do NOT depend on another accessor
    public function getRemainingAmountAttribute()
    {
        $paid = (float) $this->payments()->sum('amount');
        $total = (float) $this->total;

        return max($total - $paid, 0);
    }
}