<?php

namespace Encore\Admin\Auth\Database;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Class AdminLoginError.
 *
 * @property int $id
 *
 */
class AdminLoginError extends Model
{

    /**
     * モデルと関連しているテーブル
     *
     * @var string
     */
    protected $table = "admin_login_error";

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

    public static function addError($username, $limitMinute, $limitError)
    {
        $adminLoginError = new AdminLoginError();
        $adminLoginError->username = $username;
        $adminLoginError->save();

        $errCount = AdminLoginError::where("username",$username)->where("created_at" ,">", Carbon::now()->subMinutes($limitMinute))->count();
        if ($limitError <= $errCount){
            AdminLoginLock::lock($username);
        }
    }
}
