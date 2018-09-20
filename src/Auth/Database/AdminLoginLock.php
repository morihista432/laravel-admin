<?php

namespace Encore\Admin\Auth\Database;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class AdminLoginLock.
 *
 * @property int $id
 *
 */
class AdminLoginLock extends Model
{

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = "admin_login_lock";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'username'];

    /**
     * Create a new Eloquent model instance.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $connection = config('admin.database.connection') ?: config('database.default');

        $this->setConnection($connection);

        parent::__construct($attributes);
    }

    public static function isLocked($username,$LockMinute)
    {
        return AdminLoginLock::where("username",$username)->where("created_at" ,">", Carbon::now()->subMinutes($LockMinute))->exists();
    }

    public static function lock($username)
    {
        $adminLoginLock = new AdminLoginLock();
        $adminLoginLock->username = $username;
        $adminLoginLock->save();
    }
}
