<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Classes\DasMailer;
use App\Models\Deposit;
use App\Models\User;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Support\Facades\Session;
use Auth;
use Illuminate\Support\Str;
use MercadoPago;

class DmercadopagoController extends Controller
{

    private $access_token;

    public function __construct()
    {
        //Set Spripe Keys
        $gs = Generalsetting::findOrFail(1);
        $this->access_token = $gs->mercado_token;
    }

    public function store(Request $request) {
        $user = Auth::user();
        if (Session::has('currency'))
        {
            $curr = Currency::find(Session::get('currency'));
        }
        else
        {
            $curr = Currency::where('is_default','=',1)->first();
        }

        $available_currency = array(
            'ARS',
            'BRL',
            'CLP',
            'MXN',
            'PEN',
            'UYU',
            'VEF'
            
        );

        if(!in_array($curr->name,$available_currency))
        {
            return redirect()->back()->with('unsuccess','Invalid Currency For Mercadopago.');
        }



        $settings = Generalsetting::findOrFail(1);
        $return_url = action('User\DmercadopagoController@payreturn');
        $cancel_url = action('User\DmercadopagoController@paycancle');

        $item_name = "Deposit via Mercadopago";
        $item_number = Str::random(4).time();
        $amount = (double)$request->amount;

        MercadoPago\SDK::setAccessToken($settings->mercado_token);
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = round($amount * $curr->value,2);
        $payment->token = $request->token;
        $payment->description = 'Deposit';
        $payment->installments = 1;
        
        $payment->payer = array(
            "email" => Auth::user()->email,
        );  
        
        $payment->save();
  
                
        if ($payment->status == 'approved') {
            $txn_id = $payment->id;
            $user->balance = $user->balance + ($request->amount / $curr->value);
            $user->mail_sent = 1;
            $user->save();

            $deposit = new Deposit;
            $deposit->user_id = $user->id;
            $deposit->currency = $curr->sign;
            $deposit->currency_code = $curr->name;
            $deposit->currency_value = $curr->value;
            $deposit->amount = $request->amount / $curr->value;
            $deposit['method'] = 'MercadoPago';
            $deposit->txnid = $txn_id;
            $deposit->status = 1;
            $deposit->save();
            
            
            
                  // store in transaction table
            if ($deposit->status == 1) {
                $transaction = new Transaction;
                $transaction->txn_number = Str::random(3).substr(time(), 6,8).Str::random(3);
                $transaction->user_id = $deposit->user_id;
                $transaction->amount = $deposit->amount;
                $transaction->user_id = $deposit->user_id;
                $transaction->currency_sign = $deposit->currency;
                $transaction->currency_code = $deposit->currency_code;
                $transaction->currency_value= $deposit->currency_value;
                $transaction->method = $deposit->method;
                $transaction->txnid = $deposit->txnid;
                $transaction->details = 'Payment Deposit';
                $transaction->type = 'plus';
                $transaction->save();
            }

            if($settings->is_smtp == 1)
            {
              $data = [
                  'to' => $user->email,
                  'type' => "wallet_deposit",
                  'cname' => $user->name,
                  'damount' => ($deposit->amount * $deposit->currency_value),
                  'wbalance' => $user->balance,
                  'oamount' => "",
                  'aname' => "",
                  'aemail' => "",
                  'onumber' => "",
              ];
              $mailer = new DasMailer();
              $mailer->sendAutoMail($data);
            }
            else
            {
              $headers = "From: ".$settings->from_name."<".$settings->from_email.">";
              @mail($user->email,'Balance has been added to your account. Your current balance is: $' . $user->balance, $headers);
            }
                
                
            return redirect($return_url);

            }else{
                return redirect($cancel_url);
            }
    }


    public function paycancle(){
        $this->code_image();
         return redirect()->back()->with('unsuccess','Payment Cancelled.');
     }

     public function payreturn(){
        $this->code_image();
        return redirect()->route('user-dashboard')->with('success','Balance has been added to your account.');
     }



    // Capcha Code Image
    private function  code_image()
    {
        $actual_path = str_replace('project','',base_path());
        $image = imagecreatetruecolor(200, 50);
        $background_color = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image,0,0,200,50,$background_color);

        $pixel = imagecolorallocate($image, 0,0,255);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixel);
        }

        $font = $actual_path.'assets/front/fonts/NotoSans-Bold.ttf';
        $allowed_letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($allowed_letters);
        $letter = $allowed_letters[rand(0, $length-1)];
        $word='';
        //$text_color = imagecolorallocate($image, 8, 186, 239);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $cap_length=6;// No. of character in image
        for ($i = 0; $i< $cap_length;$i++)
        {
            $letter = $allowed_letters[rand(0, $length-1)];
            imagettftext($image, 25, 1, 35+($i*25), 35, $text_color, $font, $letter);
            $word.=$letter;
        }
        $pixels = imagecolorallocate($image, 8, 186, 239);
        for($i=0;$i<500;$i++)
        {
            imagesetpixel($image,rand()%200,rand()%50,$pixels);
        }
        session(['captcha_string' => $word]);
        imagepng($image, $actual_path."assets/images/capcha_code.png");
    }


}
