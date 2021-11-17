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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Totes extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'id';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'totes_id','name', 'PickingWaveId', 'status', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'
    ];

    /**
     * Get the creator that owns the branch.
     */
    public function creator(){
        return $this->belongsTo(User::class,'created_by');
    }

    /**
     * Get the last editor that owns the branch.
     */
    public function editor(){
        return $this->belongsTo(User::class,'updated_by');
    }

    /**
     * Get the comments for the blog post.
     */
    public function orders()
    {
        return $this->hasMany(toteOrders::class);
    }
}
