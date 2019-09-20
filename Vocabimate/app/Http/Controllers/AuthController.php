<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\RegisterResource as Reg;

use App\User; 
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; 
use Validator;

class AuthController extends Controller
{
    public $successStatus = 200;
  
    public function register(Request $request) {  
    $validator = Validator::make($request->all(), [ 
                 'login_id' => 'required',
                 'device_id'=>'required',
                 'email' => 'required|email',
                 'password' => 'required',  
                 'c_password' => 'required|same:password', 
       ]);   
    if ($validator->fails()) {          
          return response()->json(['error'=>$validator->errors()], 401);                        }    
    $input = $request->all(); 
    $x['login_id']=$input['login_id'];
    $x['email']=$input['email'];
    $a['device_id']=$input['device_id'];
    $x['password'] = bcrypt($input['password']);    
    $user = User::create($x);
    $a['user_id']=$user['user_id'];
    $success['token'] =  $user->createToken('AppName')->accessToken;
    $a['access_token']=$success['token'];
    $mob=$a;
    DB::table('mob_auth')->insert($mob);
    $plan=array();
    $p['user_id']=$user['user_id'];
    $sql = "select plan_id,plan_name,plan_description,time_period_months,actual_price,discount_price,is_hd_available,is_uhd_available,can_download,number_of_device from plan where plan_id=1";
    $comments = DB::select($sql);
    foreach ($comments as $comments) {
                
      $plan_id= $comments->plan_id;
      $actual_price= $comments->actual_price;
      $discount_price= $comments->discount_price;
   }
   $usd=DB::table('users')
   ->select('users.user_id','users.login_id','users.email','users.delete_ind','users.update_user_id','users.enabled_ind','users.account_not_expired','users.account_not_locked','users.credentials_not_expired','users.user_details_ind','mob_auth.access_token','mob_auth.login_ind')
   ->join('mob_auth','mob_auth.user_id','=','users.user_id')
   ->where("users.user_id", "=",$p)
   ->get(); 
   
   foreach ($usd as $usd) {
                
      $user_id= $usd->user_id;
      $login_id= $usd->login_id;
   }    
    DB::table('subscription')->insert(array('user_id'=>$user_id,'plan_id'=>$plan_id,'actual_price'=>$actual_price,'discount_price'=>$discount_price));
    $usd=DB::table('users')
   ->select('users.user_id','users.login_id','users.email','users.delete_ind','users.update_user_id','users.enabled_ind','users.account_not_expired','users.account_not_locked','users.credentials_not_expired','users.user_details_ind','mob_auth.access_token','mob_auth.login_ind')
   ->join('mob_auth','mob_auth.user_id','=','users.user_id')
   ->where("users.user_id", "=",$p)
   ->get();    

   $subs=DB::table('subscription')
   ->select('subscription.sub_start_date','subscription.sub_end_date','subscription.subs_status','subscription.actual_price','subscription.discount_price','subscription.paid_price','subscription.user_id')
   ->where("subscription.user_id", "=",$p)
   ->get();
   $code = array("code"=>200,'message'=>'User Created');   
   return response()->json(['userMessage' => $code,'user' => $usd, 'subscription' => $comments,'subscription_status' => $subs]);
   }
     
      
   public function login(){ 
   if(Auth::attempt(['login_id' => request('login_id'), 'password' => request('password')])){ 
      $user = Auth::user();
      $id=$user->user_id;
      $dt =now();
      $success['access_token'] =  $user->createToken('AppName')-> accessToken; 
      DB::table('mob_auth')->update($success);
      $code = array("code"=>200,'message'=>'logged in successfully');
      $cc = array("servertime"=>$dt);  
      $subs=DB::table('subscription')
      ->select('subscription.sub_id','subscription.sub_start_date','subscription.sub_end_date','subscription.subs_status','subscription.actual_price','subscription.discount_price','subscription.paid_price')
      ->where("subscription.user_id", "=",$id)
      ->get(); 
      $sql = "select plan_id,plan_name,plan_description,time_period_months,actual_price,discount_price,is_hd_available,is_uhd_available,can_download,number_of_device from plan where plan_id=1";
      $comments = DB::select($sql);
      $usd=DB::table('users')
      ->select('users.user_id','users.login_id','users.email','users.delete_ind','users.update_user_id','users.enabled_ind','users.account_not_expired','users.account_not_locked','users.credentials_not_expired','users.user_details_ind','mob_auth.access_token','mob_auth.login_ind')
      ->join('mob_auth','mob_auth.user_id','=','users.user_id')
      ->where("users.user_id", "=",$id)
      ->get();
   

       return response()->json(['userMessage' => $code,'subscription'=>$subs,'plan'=>$comments,'user'=>$usd,'token'=>$success]); 
     } else{ 
      return response()->json(['error'=>'Unauthorised'], 401); 
      } 
   }
     
   public function savedetails(Request $request) {
    $user = Auth::user();
    $id=$user->user_id;
    $validator = Validator::make($request->all(), [ 
      'first_name' => 'required',
      'middle_name'=>'max:50',
      'last_name' => 'max:50',
      'prefix' => 'min:2',  
      'gender' => 'required',
      'dob' => 'required', 
      'phone_extension' => 'max:2',
      'phone' => 'max:10|min:10',
      'address_line1' => 'required', 
      'address_line2' => 'max:250', 
      'city' => 'required', 
      'state' => 'required', 
      'zipcode' => 'required', 
      'country' => 'required',
]);   
if ($validator->fails()) {          
      return response()->json(['error'=>$validator->errors()], 401);                        }    
      $input = $request->all(); 
      $id=$user->user_id;
      $d['first_name']=$input['first_name'];
      $d['middle_name']=$input['middle_name'];
      $d['last_name']=$input['last_name'];
      $d['prefix']=$input['prefix'];
      $d['gender']=$input['gender'];
      $d['dob']=$input['dob'];
      $d['user_id']=$user['user_id'];
      $d['phone_extension']=$input['phone_extension'];
      $d['phone']=$input['phone'];
      $d['address_line1']=$input['address_line1'];
      $d['address_line2']=$input['address_line2'];
      $d['city']=$input['city'];
      $d['state']=$input['state'];
      $d['zipcode']=$input['zipcode'];
      $d['country']=$input['country'];
          
      DB::table('user_details')->insert($d);
      $code = array("code"=>200,'message'=>'details saved successfully');

      return response()->json(['success' =>$code], $this->successStatus); 
   
   }
   
   public function logout(){ 
      $user=Auth::user();
      $a=$user->token();
      $a->revoke();
      $code = array("code"=>200,'message'=>'logged out successfully');
      return response()->json(['success' => $code]);
      
   }

   public function resetpassword(Request $request){
      $user=Auth::user();
      $a=$user->password;
      $validator = Validator::make($request->all(), [ 
    'new_password' => 'max:8',
    'c_password' => 'required|same:new_password',
            ]);   
   if ($validator->fails()) {          
         return response()->json(['error'=>$validator->errors()], 401);                        }    
         $input = $request->all();
         $x['password'] = bcrypt($input['new_password']);
         DB::table('users')->update($x);

         $code = array("code"=>200,'message'=>'password hogya change');
      return response()->json(['success' => $code]);

   }

}