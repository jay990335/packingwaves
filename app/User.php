<?php

namespace App;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Image;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'biography', 'dateOfBirth', 'printer_name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function images()
    {
        return $this->morphMany('App\Image', 'profileImage');
    }

    public function image()
    {
        $id = auth()->user()->id;
        $image = Image::where('imageable_id', $id)->first();
        if(isset($image->filename)) {
            return $image;
        }else{
            $image = ['filename' => 'default_profile.jpg',
                      'imageable_id' => 1,
                      'imageable_type' => 'App\Profile'];
            return (object)$image;
        }
    }

    public function getImageUrlAttribute($id = null)
    {
        if(empty($id)){
            $id = auth()->user()->id;
        }

        $image = Image::where('imageable_id', $id)->first();
        if(isset($image->filename)) {
            return asset('/storage/app/public/image/profile/'. $image->filename);
        }else{
            return asset('/public/image/default_profile.jpg');
        }
    }

    public function getDateAttribute()
    {
        return $this->created_at->toFormattedDateString();
    }

    /**
     * Get the linnworks_token that owns the branch.
     */
    public function linnworks_token()
    {
        $id = auth()->user()->id;
        $linnworks_token = Linnworks::where('created_by', $id)->latest()->first();
        return $linnworks_token;
    }
}
