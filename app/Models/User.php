<?php

namespace App\Models;

use App\Models\Traits\HasUuid;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'delivery_time',
        'days_of_week',
        'last_delivered_at',
        'timezone',
        'delivery_time_local',
        'days_of_week',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
      'last_delivered_at' => 'datetime',
      'last_interaction_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if(empty($model->timezone))
            {
                $model->timezone = 'UTC';
            }

            if(empty($model->delivery_time_local))
            {
                $model->delivery_time_local = '0000';
            }

            $time = Carbon::createFromFormat('Hi', $model->delivery_time_local, $model->timezone);

            $time->setTimezone('UTC');

            $model->delivery_time = $time->format('Hi');
        });
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function readLaters(): HasMany
    {
        return $this->hasMany(ReadLater::class);
    }

    public function logInteraction(): void
    {
        $this->last_interaction_at = Carbon::now();
        $this->save();
    }

    public function hasDefaultDeliverySettings(): bool
    {
        if($this->getAttribute('timezone') === 'UTC' &&
            $this->getAttribute('delivery_time_local') === '0000')
        {
            return true;
        }

        return false;
    }
}
