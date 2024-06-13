<?php

namespace App\Http\Controllers\Front;

use App\Classes\DasMailer;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderTrack;
use App\Models\Pagesetting;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Support\Str;
use Session;

class SslController extends Controller
{
    public function __construct()
    {
        set_time_limit(300);
    }



    public function store(Request $request)
    {

        //  dd("hello");
        if (Session::has('currency')) {
            $curr = Currency::find(Session::get('currency'));
        } else {
            $curr = Currency::where('is_default', '=', 1)->first();
        }

        if (!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success', "You don't have any product to checkout.");
        }

        if ($request->pass_check) {
            $users = User::where('email', '=', $request->personal_email)->get();
            if (count($users) == 0) {
                if ($request->personal_pass == $request->personal_confirm) {
                    $user = new User;
                    $user->name = $request->personal_name;
                    $user->email = $request->personal_email;
                    $user->password = bcrypt($request->personal_pass);
                    $token = md5(time() . $request->personal_name . $request->personal_email);
                    $user->verification_link = $token;
                    $user->affilate_code = md5($request->name . $request->email);
                    $user->email_verified = 'Yes';
                    $user->save();
                    Auth::guard('web')->login($user);
                } else {
                    return redirect()->back()->with('unsuccess', "Confirm Password Doesn't Match.");
                }
            } else {
                return redirect()->back()->with('unsuccess', "This Email Already Exist.");
            }
        }


        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        foreach ($cart->items as $key => $prod) {
            if (!empty($prod['item']['license']) && !empty($prod['item']['license_qty'])) {
                foreach ($prod['item']['license_qty'] as $ttl => $dtl) {
                    if ($dtl != 0) {
                        $dtl--;
                        $produc = Product::findOrFail($prod['item']['id']);
                        $temp = $produc->license_qty;
                        $temp[$ttl] = $dtl;
                        $final = implode(',', $temp);
                        $produc->license_qty = $final;
                        $produc->update();
                        $temp =  $produc->license;
                        $license = $temp[$ttl];
                        $oldCart = Session::has('cart') ? Session::get('cart') : null;
                        $cart = new Cart($oldCart);
                        $cart->updateLicense($prod['item']['id'], $license);
                        Session::put('cart', $cart);
                        break;
                    }
                }
            }
        }

      //  dd($cart);


        $settings = Generalsetting::findOrFail(1);
        $order = new Order;

        $item_number = Str::random(4) . time();
        $item_amount = $request->total;
        $txnid = "SSLCZ_TXN_" . uniqid();
        $order['customer_state'] = $request->state;
        $order['shipping_state'] = $request->shipping_state;
        $order['user_id'] = $request->user_id;
        //encoding here
        $order['cart'] = utf8_encode(bzcompress(serialize($cart), 9));
        //dd(unserialize(bzdecompress(utf8_decode($order['cart']))));
        $order['totalQty'] = $request->totalQty;
        $wallet = $request->wallet_price;
        $order['pay_amount'] = round($request->total / $curr->value, 2);
        $order['method'] = $request->method;
        $order['customer_email'] = $request->email;
        $order['customer_name'] = $request->name;
        $order['customer_phone'] = $request->phone;
        $order['order_number'] = $item_number;
        $order['shipping'] = $request->shipping;
        $order['pickup_location'] = $request->pickup_location;
        $order['customer_address'] = $request->address;
        $order['customer_country'] = $request->customer_country;
        $order['customer_city'] = $request->city;
        $order['customer_zip'] = $request->zip;
        $order['shipping_email'] = $request->shipping_email;
        $order['shipping_name'] = $request->shipping_name;
        $order['shipping_phone'] = $request->shipping_phone;
        $order['shipping_address'] = $request->shipping_address;
        $order['shipping_country'] = $request->shipping_country;
        $order['shipping_city'] = $request->shipping_city;
        $order['shipping_zip'] = $request->shipping_zip;
        $order['order_note'] = $request->order_notes;
        
        $order['payment_status'] = "Pending";
        $order['currency_sign'] = $curr->sign;
        $order['currency_value'] = $curr->value;
        $order['shipping_cost'] = $request->shipping_cost;
        $order['packing_cost'] = $request->packing_cost;
        $order['shipping_title'] = $request->shipping_title;
        $order['packing_title'] = $request->packing_title;
        $order['tax'] = $request->tax;
        $order['dp'] = $request->dp;
        $order['txnid'] = $txnid;
        $order['vendor_shipping_id'] = $request->vendor_shipping_id;
        $order['vendor_packing_id'] = $request->vendor_packing_id;
        $order['wallet_price'] = round($wallet / $curr->value, 2);
        $order['referral_code'] = $request->referral_code;
        $order['referral_user_id'] = $request->referral_user_id;
        $order['referral_discount'] =$request->referral_discount;
        $order['coupon_code'] = $request->coupon_code;
        $order['coupon_discount'] = $request->coupon_discount;

        if($request->referral_code !=""){
        $order['coupon_code'] =  "REF:".$request->referral_code;
        $order['coupon_discount'] = $request->referral_discount;
        }
        

        if ($order['dp'] == 1) {
            $order['status'] = 'completed';
        }
        if (Session::has('affilate')) {
            $val = $request->total / $curr->value;
            $val = $val / 100;
            $sub = $val * $settings->affilate_charge;
            $user = User::findOrFail(Session::get('affilate'));
            if ($user) {
                if ($order['dp'] == 1) {
                    $user->affilate_income += $sub;
                    $user->update();
                }

                $order['affilate_user'] = $user->id;
                $order['affilate_charge'] = $sub;
            }
        }
        $order->save();

        /* 
           unnecessary balance deduction
           if(Auth::check()){
                Auth::user()->update(['balance' => (Auth::user()->balance - $order->wallet_price)]);
            }
            */

        if ($request->coupon_id != "") {
            $coupon = Coupon::findOrFail($request->coupon_id);
            $coupon->used++;

            if ($coupon->times != null) {
                $i = (int)$coupon->times;
                $i--;
                $coupon->times = (string)$i;
            }
            $coupon->update();
        }

        foreach ($cart->items as $prod) {
            $x = (string)$prod['stock'];
            if ($x != null) {
                $product = Product::findOrFail($prod['item']['id']);
                $product->stock =  $prod['stock'];
                $product->update();
            }
        }

        foreach ($cart->items as $prod) {
            $x = (string)$prod['size_qty'];
            if (!empty($x)) {
                $product = Product::findOrFail($prod['item']['id']);
                $x = (int)$x;
                $x = $x - $prod['qty'];
                $temp = $product->size_qty;
                $temp[$prod['size_key']] = $x;
                $temp1 = implode(',', $temp);
                $product->size_qty =  $temp1;
                $product->update();
            }
        }

        foreach ($cart->items as $prod) {
            $x = (string)$prod['stock'];
            if ($x != null) {
                $product = Product::findOrFail($prod['item']['id']);
                $product->stock =  $prod['stock'];
                $product->update();
                if ($product->stock <= 5) {
                    $notification = new Notification;
                    $notification->product_id = $product->id;
                    $notification->save();

                    $gs = Generalsetting::first();
                    /*    if($gs->is_smtp == 1)
                        {
                            $maildata = [
                                'to' => $product->user->email,
                                'subject' => 'Out of Stock Alert!',
                                'body' => "One of your product is almost out of stock (less or equal to 5).\n<strong>Product Link: </strong> <a target='_blank' href='".url('/').'/'.'item/'.$product->slug."'>".$product->name."</a>",
                            ];
                            $mailer = new DasMailer();
                            $mailer->sendCustomMail($maildata);
                        }
                        else
                        {
                            $to = $product->user->email;
                            $subject = 'Out of Stock Alert!';
                            $msg = "One of your product is almost out of stock (less or equal to 5).\n<strong>Product Link: </strong> <a target='_blank' href='".url('/').'/'.'item/'.$product->slug."'>".$product->name."</a>";
                            $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                            mail($to,$subject,$msg,$headers);
                        } */
                }
            }
        }


        $notf = null;

        foreach ($cart->items as $prod) {
            if ($prod['item']['user_id'] != 0) {
                $vorder =  new VendorOrder;
                $vorder->order_id = $order->id;
                $vorder->user_id = $prod['item']['user_id'];
                $notf[] = $prod['item']['user_id'];
                $vorder->qty = $prod['qty'];
                $vorder->price = $prod['price'];
                $vorder->order_number = $order->order_number;
                $vorder->save();
                if ($order->dp == 1) {
                    $vorder->user->update(['current_balance' => $vorder->user->current_balance += $prod['price']]);
                }
            }
        }

        if (!empty($notf)) {
            $users = array_unique($notf);
            foreach ($users as $user) {
                $notification = new UserNotification;
                $notification->user_id = $user;
                $notification->order_number = $order->order_number;
                $notification->save();
            }
        }


        $gs = Generalsetting::find(1);


        //Sending Email To Buyer
        /* 
            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $request->email,
                    'type' => "new_order",
                    'cname' => $request->name,
                    'oamount' => "",
                    'aname' => "",
                    'aemail' => "",
                    'wtitle' => "",
                    'onumber' => $order->order_number,
                ];

                $mailer = new DasMailer();
                $mailer->sendAutoOrderMail($data,$order->id);            
            }
            else
            {
                $to = $request->email;
                $subject = "Your Order Placed!!";
                $msg = "Hello ".$request->name."!\nYou have placed a new order.\nYour order number is ".$order->order_number.".Please wait for your delivery. \nThank you.";
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);            
            }
            //Sending Email To Admin
            if($gs->is_smtp == 1)
            {
                $data = [
                    'to' => $gs->header_email,
                    'subject' => "New Order Recieved!!",
                    'body' => "Hello Admin!<br>Your store has received a new order.<br>Order Number is ".$order->order_number.".Please login to your panel to check. <br>Thank you.",
                ];

                $mailer = new DasMailer();
                $mailer->sendCustomMail($data);            
            }
            else
            {
                $to = $gs->from_email;
                $subject = "New Order Recieved!!";
                $msg = "Hello Admin!\nYour store has recieved a new order.\nOrder Number is ".$order->order_number.".Please login to your panel to check. \nThank you.";
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }

        */


        Session::put('tempcart', $cart);
        Session::forget('cart');
        Session::forget('pickup_text');
        Session::forget('pickup_cost');
        Session::forget('pickup_costshow');

        Session::put('temporder', $order);


        $post_data = array();
        $post_data['store_id'] = $settings->ssl_store_id;
        $post_data['store_passwd'] = $settings->ssl_store_password;
        $post_data['total_amount'] = $item_amount;
        $post_data['currency'] = $curr->name;
        $post_data['tran_id'] = $txnid;
        $post_data['success_url'] = action('Front\SslController@notify');
        $post_data['fail_url'] =  action('Front\SslController@cancle');
        $post_data['cancel_url'] =  action('Front\SslController@cancle');
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        $input = $request->all();
        # CUSTOMER INFORMATION
        $post_data['cus_name'] = $input['name'];
        $post_data['cus_email'] = $input['email'];
        $post_data['cus_add1'] = $input['address'];
        $post_data['cus_city'] = $input['city'];
        $post_data['cus_state'] = $input['state'];
        $post_data['cus_postcode'] = $input['zip'];
        $post_data['cus_country'] = $input['customer_country'];
        $post_data['cus_phone'] = $input['phone'];
        $post_data['cus_fax'] = $input['phone'];



        # REQUEST SEND TO SSLCOMMERZ
        if ($settings->ssl_sandbox_check == 1) {
            $direct_api_url = "https://sandbox.sslcommerz.com/gwprocess/v3/api.php";
        } else {
            $direct_api_url = "https://securepay.sslcommerz.com/gwprocess/v3/api.php";
        }




        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $direct_api_url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);


        // dd($content);
        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $sslcommerzResponse = $content;
        } else {
            curl_close($handle);
            return redirect()->back()->with('unsuccess', "FAILED TO CONNECT WITH SSLCOMMERZ API");
            exit;
        }

        # PARSE THE JSON RESPONSE
        $sslcz = json_decode($sslcommerzResponse, true);


        if (isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL'] != "") {

            # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
            # echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
            echo "<meta http-equiv='refresh' content='0;url=" . $sslcz['GatewayPageURL'] . "'>";
            # header("Location: ". $sslcz['GatewayPageURL']);
            exit;
        } else {
            return redirect()->back()->with('unsuccess', "JSON Data parsing error!");
        }
    }// end store

    public function cancle(Request $request)
    {
        $this->code_image();
        return redirect()->route('front.checkout')->with('unsuccess', 'Payment Cancelled.');
    }

    public function notify(Request $request)
    {
        $success_url = action('Front\PaymentController@payreturn');
        $cancel_url = action('Front\PaymentController@paycancle');
        $input = $request->all();
        // dd($response);
        if ($input['status'] == 'VALID') {

            $order = Order::where('txnid', $input['tran_id'])->first();
            $data['payment_status'] = 'Completed';
            if ($order->dp == 1) {
                $data['status'] = 'completed';
            }

            // dd($order->wallet_price);
            $order->update($data);
            $transactionCodeUser = new \App\Models\Transaction;
                                $transactionCodeUser->txn_number = "B" . Str::random(2) . substr(time(), 6, 8) . Str::random(2);
                                $transactionCodeUser->user_id = $order->user_id;
                                $transactionCodeUser->amount = $order->referral_discount;
                                $transactionCodeUser->currency_sign = $order->currency_sign;
                                $transactionCodeUser->currency_code = \App\Models\Currency::where('sign', $order->currency_sign)->first()->name;
                                $transactionCodeUser->currency_value = $order->currency_value;
                                $transactionCodeUser->details = 'Received Discount';
                                $transactionCodeUser->type = 'discount';
                                $transactionCodeUser->save();
                                

            if ($order->wallet_price != 0) {
                $user = User::find($order->user_id);

                // dd($user->balance);
                $user->balance -= $order->wallet_price;
                $user->update();
            } else {
                // bonus distribution
                $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
                $code_user = User::find($order->user_id);
                $code_owner = User::find($order->referral_user_id);

                // dd($user->balance);
               
                foreach ($cart->items as $item) {
                      //  dd($item);
                        //code owner
                        $referral_owner_bonus = round($item['qty'] * $item['referral_bonus'], 2);
                        //code user
                        $referral_user_bonus = round($item['qty'] * $item['referral_user_bonus'], 2);
                        $sku = $item['sku'];
                        $is_referral_bonus = $item['is_referral_bonus'];
                        //referral code user
                       // dd($order->user_id.'--'.$order->referral_user_id);
                        if ($is_referral_bonus == 1 ) {

                            if(($order->user_id==$order->referral_user_id) || $order->referral_user_id==""){
                                // no bonus balance
                               // dd("condition-matched");
                            }else{

                                 // dd($order->user_id.'--'.$order->referral_user_id);
                                /*
                                $transactionCodeUser = new \App\Models\Transaction;
                                $transactionCodeUser->txn_number = "B" . Str::random(2) . substr(time(), 6, 8) . Str::random(2);
                                $transactionCodeUser->user_id = $order->user_id;
                                $transactionCodeUser->amount = $referral_user_bonus;
                                $transactionCodeUser->currency_sign = $order->currency_sign;
                                $transactionCodeUser->currency_code = \App\Models\Currency::where('sign', $order->currency_sign)->first()->name;
                                $transactionCodeUser->currency_value = $order->currency_value;
                                $transactionCodeUser->details = 'Received Bonus sku:' . $sku;
                                $transactionCodeUser->type = 'plus';
                                $transactionCodeUser->save();
                                $code_user->balance += $referral_user_bonus;
                                $code_user->update();
                                */
                                // referral code owner
                                $transactionCodeOwner = new \App\Models\Transaction;
                                $transactionCodeOwner->txn_number = "B" . Str::random(2) . substr(time(), 6, 8) . Str::random(2);
                                $transactionCodeOwner->user_id = $order->referral_user_id;
                                $transactionCodeOwner->amount = $referral_owner_bonus;
                                $transactionCodeOwner->currency_sign = $order->currency_sign;
                                $transactionCodeOwner->currency_code = \App\Models\Currency::where('sign', $order->currency_sign)->first()->name;
                                $transactionCodeOwner->currency_value = $order->currency_value;
                                $transactionCodeOwner->details = 'Received Bonus sku:' . $sku;
                                $transactionCodeOwner->type = 'plus';
                                $transactionCodeOwner->save();
                                $code_owner->balance += $referral_owner_bonus;
                                $code_owner->update();
                        }

                          



                        } // end if product is available for bonus
                        
                        


                   
                }// end first foreach

                if ($order->dp == 1) {
                    $track = new OrderTrack;
                    $track->title = 'Completed';
                    $track->text = 'Your order has completed successfully.';
                    $track->order_id = $order->id;
                    $track->save();
                } else {
                    $track = new OrderTrack;
                    $track->title = 'Pending';
                    $track->text = 'You have successfully placed your order.';
                    $track->order_id = $order->id;
                    $track->save();
                }


                if ($order->user_id != 0 && $order->wallet_price != 0) {
                    $transaction = new \App\Models\Transaction;
                    $transaction->txn_number = Str::random(3) . substr(time(), 6, 8) . Str::random(3);
                    $transaction->user_id = $order->user_id;
                    $transaction->amount = $order->wallet_price;
                    $transaction->currency_sign = $order->currency_sign;
                    $transaction->currency_code = \App\Models\Currency::where('sign', $order->currency_sign)->first()->name;
                    $transaction->currency_value = $order->currency_value;
                    $transaction->details = 'Payment Via Wallet Order No:' . $order->order_number;
                    $transaction->type = 'minus';
                    $transaction->save();
                }
            } // end if input status valid

            //



            $notification = new Notification;
            $notification->order_id = $order->id;
            $notification->save();

            $tempcart = unserialize(bzdecompress(utf8_decode($order->cart)));
            return redirect($success_url)->with(['tempcart' => $tempcart, 'temporder' => $order]);
        } else {
            $order = Order::where('txnid', $input['tran_id'])->first();
            $order->delete();
            return redirect($cancel_url);
        }
    }


    // Capcha Code Image
    private function  code_image()
    {
        // dd("hello");
        $actual_path = public_path();
        $image = imagecreatetruecolor(200, 50);
        $background_color = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, 200, 50, $background_color);

        $pixel = imagecolorallocate($image, 0, 0, 255);
        for ($i = 0; $i < 500; $i++) {
            imagesetpixel($image, rand() % 200, rand() % 50, $pixel);
        }
        //dd($actual_path);
        $font = $actual_path . '/assets/front/fonts/NotoSans-Bold.ttf';
        // dd($font);
        $allowed_letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $length = strlen($allowed_letters);
        $letter = $allowed_letters[rand(0, $length - 1)];
        $word = '';
        //$text_color = imagecolorallocate($image, 8, 186, 239);
        $text_color = imagecolorallocate($image, 0, 0, 0);
        $cap_length = 6; // No. of character in image
        for ($i = 0; $i < $cap_length; $i++) {
            $letter = $allowed_letters[rand(0, $length - 1)];
            // dd($image);
            imagettftext($image, 25, 1, 35 + ($i * 25), 35, $text_color, $font, $letter);
            $word .= $letter;
        }
        $pixels = imagecolorallocate($image, 8, 186, 239);
        for ($i = 0; $i < 500; $i++) {
            imagesetpixel($image, rand() % 200, rand() % 50, $pixels);
        }
        session(['captcha_string' => $word]);
        imagepng($image, $actual_path . "/assets/images/capcha_code.png");
    }
}
