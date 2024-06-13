<?php

namespace App\Http\Controllers\Front;

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

class FosterController extends Controller
{
    //



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
        $order['referral_discount'] = $request->referral_discount;
        $order['coupon_code'] = $request->coupon_code;
        $order['coupon_discount'] = $request->coupon_discount;

        if ($request->referral_code != "") {
            $order['coupon_code'] =  "REF:" . $request->referral_code;
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

      
        # $post_data['multi_card_name'] = "mastercard,visacard,amexcard";  # DISABLE TO DISPLAY ALL AVAILABLE

        $input = $request->all();
    
        //foster
  
        $redirect_url =  action('Front\FosterController@notify');
        $cancel_url = action('Front\FosterController@cancle');
        $fail_url = action('Front\FosterController@cancle');
        $urlparamForHash = http_build_query(array(
            'mcnt_AccessCode' => '200914123320',
            'mcnt_TxnNo' => $txnid, //Ymdhmsu//PNR 
            'mcnt_ShortName' => 'Mycell',
            'mcnt_OrderNo' => $txnid,
            'mcnt_ShopId' => '247',
            'mcnt_Amount' => $item_amount,
            'mcnt_Currency' => 'BDT'
        ));
        $secretkey = 'd82bde13d2ae062185cdc4b2ec2cba16';
        $secret = strtoupper($secretkey);
        $hashinput = hash_hmac('SHA256', $urlparamForHash, $secret);



        $domain = "https://mycellmobile.com"; // $_SERVER["SERVER_NAME"]; // or Manually put your domain name  
      //  $ip =request()->server('SERVER_ADDR');  //domain ip  
        $ip = "23.227.186.26";
        //echo $ip."======================";




        $urlparam = array(
            'mcnt_TxnNo' => $txnid,
            'mcnt_ShortName' => 'Mycell', //No Need to Change       
            'mcnt_OrderNo' =>  $txnid,
            'mcnt_ShopId' => '247', //No Need to Change 
            'mcnt_Amount' => $item_amount,
            'mcnt_Currency' => 'BDT',
            'cust_InvoiceTo' => $input['name'],
            'cust_CustomerServiceName' => 'E-commarce', //must
            'cust_CustomerName' => $input['name'], //must 
            'cust_CustomerEmail' => $input['email'], //must  
            'cust_CustomerAddress' => $input['address'],
            'cust_CustomerContact' => $input['phone'], //must 
            'cust_CustomerGender' => 'N/A',
            'cust_CustomerCity' => $input['city'], //must 
            'cust_CustomerState' => $input['state'],
            'cust_CustomerPostcode' => $input['zip'],
            'cust_CustomerCountry' => 'Bangladesh',
            'cust_Billingaddress' => 'Bangladesh', //must if not put ‘N/A’ 
            'cust_ShippingAddress' => 'Bangladesh',
            'cust_orderitems' => 1, //must  
            'GW' => '', //optional        
            'CardType' => '', //optional
            'success_url' => $redirect_url, //must   
            'cancel_url' => $cancel_url, //must   
            'fail_url' => $fail_url, //must  
            'emi_amout_per_month' => '', //optional 
            'emi_duration' => '', //optional                           
            'merchentdomainname' => $domain, // must           
            'merchentip' => $ip,
            'mcnt_SecureHashValue' => $hashinput
        );
       // $url = 'https://demo.fosterpayments.com.bd/fosterpayments/paymentrequest.php';
        $url ='https://payment.fosterpayments.com.bd/fosterpayments/paymentrequest.php';


        $data_string = json_encode($urlparam);
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_TIMEOUT, 30);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC
        curl_setopt(
            $handle,
            CURLOPT_HTTPHEADER,
            array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string))
        );
        $content = curl_exec($handle);

        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);


        // dd($content);
        if ($code == 200 && !(curl_errno($handle))) {
            curl_close($handle);
            $response = $content;
        } else {
            curl_close($handle);
            return redirect()->back()->with('unsuccess', "FAILED TO CONNECT WITH FOSTER API");
            exit;
        }
        
        $responsedate = json_decode($response, true);

       // dd($responsedate);
        //echo $response ;
        $data = $responsedate['data'];
        
        //  die;
        $redirect_url = $data['redirect_url'];
        $payment_id = $data['payment_id'];


        $url = $redirect_url . "?payment_id=" . $payment_id;

        echo "<meta http-equiv='refresh' content='0;url=" . $url . "'>";
        exit;
        //dd($url);
        // echo $url;

        //end foster













    } // end store
}
