<?php

namespace App\Http\Controllers;

use DB;
use App\Models\API;
use App\Models\Role;
use App\Models\User;
use App\Models\Admin;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{

    function index (Request $request) {
        $admins = Admin::with('user')->paginate(10);
        return view('admin.admin.index', ['admins' => $admins]);
    }

    function create () {
        $roles = Role::where('status', 'active')->orderBy('created_at','DESC')->get();

        return view('admin.admin.create', compact('roles'));
    }

    function store (Request $request) {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'nullable',
            'phone' => 'nullable',
            'permissions' => 'required',
            'email' => 'required|unique:users',
            'status' => 'nullable',
        ]);
        
        if(!empty($request->password)){
            $password =  Hash::make($request->password);
        }else{
            $password = Hash::make(staffDefaultPassword());
        }

        DB::transaction(function () use($request, $password){
            $user = User::create([
                'firstname' => $request->first_name,
                'lastname' => $request->last_name,
                'email'=> $request->email,
                'phone' => $request->phone,
                'password' => $password,
                'status' => $request->status,
                'type' => 'admin',
                'username' => Str::slug($request->firstname . '-' . $request->lastname),
            ]);

            $admins = Admin::create([
                'user_id' => $user->id,
                'permissions' => join(',', $request->permissions),
            ]);
        });

        return redirect(route('admins'))->with('message', 'Account created successfully!');
    }

    function view (Request $request) {
        if (!is_numeric($request->admin)) return back()->with('error', 'Account not found');
        $admin = User::with('admin')->find($request->admin);
        $roles = Role::where('status','active')->get();
        $permissions = explode(",",$admin->admin->permissions);
        
        return view('admin.admin.edit', ['admin' => $admin, 'roles' => $roles, 'permissions' => $permissions]);
    }

    function update (Request $request) {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'password' => 'nullable',
            'phone' => 'nullable',
            'roles' => 'required',
            'email' => 'required|unique:users,email,'.$request->id,
            'status' => 'nullable',
        ]);

        // 'unique:table,email_column_to_check,id_to_ignore'
       
        $admin = User::find($request->id);

        if (!empty($request->password)) {
            $password =  Hash::make($request->password);
        } else {
            $password = $admin->password;
        }

        $admin->update([
            'email' => $request->email,
            'firstname' => $request->first_name,
            'lastname' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'status' => $request->status,
            'password' => $password,
        ]);

        $admin->admin->update([
            'permissions' => join(',', $request->roles),
        ]);
        
        if ($admin) return back()->with('message', 'Account updated successfully!');
        else return back()->with('error', 'Account update failed!');
    }

    function verifyBiller () {
        $products = Category::join('products', 'category_id', '=', 'categories.id')->whereIn('unique_element', verifiableUniqueElements())->get('products.*');
        return view('admin.admin.verify-biller', ['products' => $products, 'api' => API::where('id', 1)->get()]);
    }

    function verifyPost (Request $request) {
        $val = validator($request->all(), [
            'value' => 'required',
            'product' => 'required',
            'api' => 'required',
        ]);
    
        if ($val->fails()) return ['code' => 0, 'message' => $val->errors()->first()];
        $api = API::find($request->api);
        $data['request']['variation_name'] = $request->type;
        $data['request']['unique_element'] = $request->value;
        $data['request']['product_slug'] = $request->product;
        $data['api'] = $api;
        $product = Product::where('slug', $request->product)->first();
        $verify = app("App\Http\Controllers\Providers\KingsVtuController")->verify($data, true);
        
        if (isset($verify) && $verify['status_code'] == 1) {
            if (isset($verify['raw_response'])) {
                $this->refineAndLogBiller($verify, $product->category, $request->value, $product->slug);
            }
        }
        
        return $verify;
    }
}
