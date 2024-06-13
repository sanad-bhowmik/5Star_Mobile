<?php

namespace App\Http\Controllers\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\User;
use App\Classes\DasMailer;
use App\Models\Notification;
use App\Models\RegistrationBonus;
use Auth;
use Illuminate\Support\Facades\Input;
use Validator;
use Illuminate\Support\Str;
use Session;
use App\Models\Currency;

class RegisterController extends Controller
{

    public function register(Request $request)
    {

    	$gs = Generalsetting::findOrFail(1);

    	if($gs->is_capcha == 1)
    	{
	        $value = session('captcha_string');
	        if ($request->codes != $value){
	            return response()->json(array('errors' => [ 0 => 'Please enter Correct Capcha Code.' ]));
	        }
    	}


        //--- Validation Section

        $rules = [
		        'email'   => 'required|email|unique:users',
		        'password' => 'required|confirmed'
                ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

	        $user = new User;
	        $input = $request->all();
	        $input['password'] = bcrypt($request['password']);
	        $token = md5(time().$request->name.$request->email);
	        $input['verification_link'] = $token;
	        $input['affilate_code'] = md5($request->name.$request->email);
			// transaction details
			$bonus = RegistrationBonus::first();
			$input['balance'] = $bonus->bonus;


	          if(!empty($request->vendor))
	          {
					//--- Validation Section
					$rules = [
						'shop_name' => 'unique:users',
						'shop_number'  => 'max:10'
							];
					$customs = [
						'shop_name.unique' => 'This Shop Name has already been taken.',
						'shop_number.max'  => 'Shop Number Must Be Less Then 10 Digit.'
					];

					$validator = Validator::make($request->all(), $rules, $customs);
					if ($validator->fails()) {
					return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
					}
					$input['is_vendor'] = 1;

			  }

			$user->fill($input)->save();



			if ($user->id != 0 && $bonus->bonus != 0) {
				if (Session::has('currency')) {
					$curr = Currency::find(Session::get('currency'));
				}
				else {
					$curr = Currency::where('is_default','=',1)->first();
				}

                $transaction = new \App\Models\Transaction;
                $transaction->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
                $transaction->user_id = $user->id;
                $transaction->amount = $bonus->bonus ;
                $transaction->currency_sign = $curr->sign;
                $transaction->currency_code = \App\Models\Currency::where('sign',$curr->sign)->first()->name;
                $transaction->currency_value= $curr->value;
                $transaction->details = 'New Registration Bonus';
                $transaction->type = 'plus';
                $transaction->save();
            }




	        if($gs->is_verification_email == 1)
	        {
	        $to = $request->email;
	        $subject = 'Verify your email address.';
	        $msg = "Dear Customer,<br> We noticed that you need to verify your email address. <a href=".url('user/register/verify/'.$token).">Simply click here to verify. </a>";
	        //Sending Email To Customer
	        if($gs->is_smtp == 1)
	        {
	        $data = [
	            'to' => $to,
	            'subject' => $subject,
	            'body' => $msg,
	        ];

	        $mailer = new DasMailer();
	        $mailer->sendCustomMail($data);
	        }
	        else
	        {
	        $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
	        mail($to,$subject,$msg,$headers);
	        }
          	return response()->json('We need to verify your email address. We have sent an email to '.$to.' to verify your email address. Please click link in that email to continue.');
	        }
	        else {

            $user->email_verified = 'Yes';
			$user->referral_code=Str::random(2).substr(time(), 6,8).Str::random(2).$user->id;
            $user->update();
	        $notification = new Notification;
	        $notification->user_id = $user->id;
	        $notification->save();
            Auth::guard('web')->login($user);
          	return response()->json(1);
	        }

    }

    public function token($token)
    {
        $gs = Generalsetting::findOrFail(1);

        if($gs->is_verification_email == 1)
	        {
        $user = User::where('verification_link','=',$token)->first();
        if(isset($user))
        {
            $user->email_verified = 'Yes';
            $user->update();
	        $notification = new Notification;
	        $notification->user_id = $user->id;
	        $notification->save();
            Auth::guard('web')->login($user);
            return redirect()->route('user-dashboard')->with('success','Email Verified Successfully');
        }
    		}
    		else {
    		return redirect()->back();
    		}
    }
}
