<?php

namespace App;

use App\Scopes\RestorantScope;
use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasConfig;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasConfig;

    protected static function booted(){
        static::addGlobalScope(new RestorantScope);
    }


    protected $modelName="App\Quotation";

    protected $table = 'quotations';

    protected $appends = ['time_created','time_formated','last_status'];

    public function restorant()
    {
        return $this->belongsTo(\App\Restorant::class);
    }

    public function address()
    {
        return $this->hasOne(\App\Address::class, 'id', 'address_id');
    }

    public function client()
    {
        return $this->hasOne(\App\User::class, 'id', 'client_id');
    }

    public function status()
    {
        return $this->belongsToMany(\App\Status::class, 'quotation_has_status', 'quotation_id', 'status_id')->withPivot('user_id', 'created_at', 'comment')->quotationBy('quotation_has_status.id', 'ASC');
    }

    public function laststatus()
    {
        return $this->belongsToMany(\App\Status::class, 'quotation_has_status', 'quotation_id', 'status_id')->withPivot('user_id', 'created_at', 'comment')->quotationBy('quotation_has_status.id', 'DESC')->limit(1);
    }

    public function getLastStatusAttribute()
    {
        return $this->belongsToMany(\App\Status::class, 'quotation_has_status', 'quotation_id', 'status_id')->withPivot('user_id', 'created_at', 'comment')->quotationBy('quotation_has_status.id', 'DESC')->limit(1)->get();
    }

    public function getIsPreparedAttribute()
    {
        return $this->belongsToMany(\App\Status::class, 'quotation_has_status', 'quotation_id', 'status_id')->where('status_id',5)->count()==1;
    }

    public function items()
    {
        return $this->belongsToMany(\App\Items::class, 'quotation_has_items', 'quotation_id', 'item_id')->withPivot(['qty', 'extras', 'vat', 'vatvalue', 'variant_price', 'variant_name']);
    }

    public function ratings()
    {
        return $this->belongsToMany(\App\Ratings::class, 'ratings', 'quotation_id', 'id');
    }

    public function getTimeCreatedAttribute(){
        return $this->created_at?$this->created_at->format(config('settings.datetime_display_format')):null;
    }

    public function getTimeFormatedAttribute()
    {
        $parts = explode('_', $this->delivery_pickup_interval);
        if (count($parts) < 2) {
            return '';
        }

        $hoursFrom = (int) (($parts[0] / 60).'');
        $minutesFrom = $parts[0] - ($hoursFrom * 60);

        $hoursTo = (int) (($parts[1] / 60).'');
        $minutesTo = $parts[1] - ($hoursTo * 60);

        $format = 'G:i';
        if (config('settings.time_format') == 'AM/PM') {
            $format = 'g:i A';
        }
        $from = date($format, strtotime($hoursFrom.':'.$minutesFrom));
        $to = date($format, strtotime($hoursTo.':'.$minutesTo));

        return $from.' - '.$to;
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function (self $quotation) {
            //Delete Quotation items
            $quotation->items()->detach();
            
            //Delete Oders statuses
            $quotation->status()->detach();

            //Delete Oders ratings
            $quotation->ratings()->detach();

            return true;
        });
    }


}
