<?php

namespace Encore\Admin\Controllers;


use Carbon\Carbon;
use Encore\Admin\Auth\Database\Administrator;
use Encore\Admin\Auth\Database\AdminLoginLock;
use Encore\Admin\Auth\Database\AdminLoginError;
use Encore\Admin\Facades\Admin;
use Encore\Admin\Form;
use Encore\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Encore\Admin\Auth\Database\OperationLog as OperationLogModel;


class AuthController extends Controller
{
    /**
     * Login page.
     *
     * @return \Illuminate\Contracts\View\Factory|Redirect|\Illuminate\View\View
     */
    public function getLogin()
    {
        if (!Auth::guard('admin')->guest()) {
            return redirect(config('admin.route.prefix'));
        }

        return view('admin::login');
    }

    /**
     * @param Request $request
     *
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $credentials = $request->only(['username', 'password']);

        $validator = Validator::make($credentials, [
            'username' => 'required', 'password' => 'required',
        ]);

        if ($validator->fails()) {
            return Redirect::back()->withInput()->withErrors($validator);
        }

        if (AdminLoginLock::isLocked( $credentials['username'],config('admin.account_lock.lock_minutes')))
        {
            Log::info("account locked. : " . $credentials['username']);
            return Redirect::back()->withInput()->withErrors(['username' => $this->getFailedLoginMessage()]);
        }



        if (Auth::guard('admin')->attempt($credentials)) {
            admin_toastr(trans('admin.login_successful'));

            // log add operation log
            $log = [
                'user_id' => Admin::user()->id,
                'path'    => $request->path(),
                'method'  => $request->method(),
                'ip'      => $request->getClientIp(),
                'input'   => json_encode(
                    [
                        'DATE' => Carbon::now()->format("Y/m/d H:i:s"),
                        'HTTP_USER_AGENT' =>  $_SERVER['HTTP_USER_AGENT'],
                        'REMOTE_IP' => $request->getClientIp(),
                        'REMOTE_HOST' => gethostbyaddr($request->getClientIp())
                    ]
                ),
            ];

            OperationLogModel::create($log);

            return redirect()->intended(config('admin.route.prefix'));
        }

        // error save
        AdminLoginError::addError($credentials['username'],config('admin.account_lock.limit_minutes'),config('admin.account_lock.limit_error'));

        return Redirect::back()->withInput()->withErrors(['username' => $this->getFailedLoginMessage()]);
    }

    /**
     * User logout.
     *
     * @return Redirect
     */
    public function getLogout()
    {
        Auth::guard('admin')->logout();

        session()->forget('url.intented');

        return redirect(config('admin.route.prefix'));
    }

    /**
     * User setting page.
     *
     * @return mixed
     */
    public function getSetting()
    {
        return Admin::content(function (Content $content) {
            $content->header(trans('admin.user_setting'));
            $form = $this->settingForm();
            $form->tools(
                function (Form\Tools $tools) {
                    $tools->disableBackButton();
                    $tools->disableListButton();
                }
            );
            $content->body($form->edit(Admin::user()->id));
        });
    }

    /**
     * Update user setting.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSetting()
    {
        return $this->settingForm()->update(Admin::user()->id);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        return Administrator::form(function (Form $form) {
            $form->display('username', trans('admin.username'));
            $form->text('name', trans('admin.name'))->rules('required|string');
            if (empty(config('admin.hidden_avatar')) || !config('admin.hidden_avatar')) {
                $form->image('avatar', trans('admin.avatar'));
            }
            $form->password('password_current', trans('admin.password_current'))->rules('required|string|admin_current_password',
                [
                    'admin_current_password' => '現在のパスワードを入力してください。'
                ]);
            $form->password('password', trans('admin.password'))->rules('confirmed|required|string|min:8|regex:/\A(?=.*?[a-z])(?=.*?\d)(?=.*?[!-\/:-@[-`{-~])[!-~]{8,100}+\z/i',
                [
                    'regex' => '英数記号(!"\#$%&@\'()*+,-./_)を使い8文字以上で設定ください。'
                ]);
            $form->password('password_confirmation', trans('admin.password_confirmation'))->rules('required|string')
                ->default(function ($form) {
                    return $form->model()->password;
                });

            $form->setAction(admin_base_path('auth/setting'));

            $form->ignore(['password_confirmation','password_current']);

            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = bcrypt($form->password);
                }
            });

            $form->saved(function () {
                admin_toastr(trans('admin.update_succeeded'));

                return redirect(admin_base_path('auth/setting'));
            });
        });
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage()
    {
        return Lang::has('auth.failed')
            ? trans('auth.failed')
            : 'These credentials do not match our records.';
    }
}
