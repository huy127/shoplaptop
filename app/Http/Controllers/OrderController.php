<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Bill;
use App\Models\Product;
use App\Models\Customer;
use DNS2D;
use App\Models\BillDetail;
use App\Models\Statistical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function getDonHang(Request $req)
    {
        if (Auth::check()) {


            $donhang = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->orderby('id_bill', 'DESC')->get();
            $url_canonical = $req->url();


            return view('admin.QL_donhang', compact('donhang','url_canonical'));


        } else {
            return redirect()->route('trang-chu');
        }
    }
    public function getDonHang_daduyet(Request $req)
    {
        if (Auth::check()) {

            $donhang_daduyet = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->where('status_bill',1)->orderby('id_bill', 'desc')->get();
            $url_canonical = $req->url();


            return view('admin.QL_donhang_daduyet', compact('donhang_daduyet','url_canonical'));


        } else {
            return redirect()->route('trang-chu');
        }
    }
    public function getDonHang_chuaduyet(Request $req)
    {
        if (Auth::check()) {

            $donhang_chuaduyet = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->where('status_bill',0)->orderby('id_bill', 'desc')->get();
            $url_canonical = $req->url();


            return view('admin.QL_donhang_chuaduyet', compact('donhang_chuaduyet', 'url_canonical'));


        } else {
            return redirect()->route('trang-chu');
        }
    }
    public function getDonHang_huy(Request $req)
    {
        if (Auth::check()) {

            $donhang_huy = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->where('status_bill',2)->orderby('id_bill', 'desc')->get();
            $url_canonical = $req->url();


            return view('admin.QL_donhang_huy', compact('donhang_huy','url_canonical'));


        } else {
            return redirect()->route('trang-chu');
        }
    }


    public function DelAdmin_DonHang($id)
    {

        $bill = Bill::where('id_bill', $id)->first();

        Customer::where('id', $bill->id_customer)->first()->delete();

        $billdetail = BillDetail::where('id_bill', $bill->id_bill)->delete();


        Bill::where('id_bill',$id)->delete();

        return redirect()->back()->with('thongbao', 'Xóa thành công!');
    }

    public function getChiTietDonHang($id, Request $req)
    {
        if (Auth::check()) {


            $billdetaill =DB::select("SELECT bt.id_bill_detail, bt.id_bill, bt.id_product, bt.id_post_bill_detail, bt.order_code, bt.quantity,
        bt.unit_price,p.sub_image,p.image,p.hours_sale,p.date_sale, p.product_quantity ,p.id_post, post.sp_vi as sp_vi,  post.sp_en as sp_en
        FROM bill_detail bt, products p
        INNER JOIN post ON p.id_post = post.id_post
         WHERE bt.id_product=p.id AND id_bill=$id ");
            $url_canonical = $req->url();

            $thongtin_kh = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->where('id_bill',$id)->get();




            return view('admin.ChitietDH', compact('billdetaill', 'thongtin_kh', 'url_canonical'));
        } else {
            return redirect()->route('trang-chu');
        }
    }
    public function postChiTietDonHang($id, Request $req){

        $qty_update = BillDetail::where('id_bill', $id)->where('order_code',$req->order_code)->first();
        // dd( $qty_update);
        $qty_update->quantity = $req->product_quantity_order;
        $qty_update->save();

        $total_update = Bill::where('id_bill', $id)->where('order_code',$req->order_code)->first();
        $total_update->total = $req->product_quantity_order * $qty_update->unit_price;
        $total_update->save();

        return redirect()->back();
    }


    public function update_order_qty(Request $req){
        $data = $req->all();

        $bill = Bill::find($data['order_id']);
        $bill->status_bill = $data['order_status'];
        $bill->save();

        //order date
        $order_date  = $bill->date_order;
        $statistic = Statistical::where('order_date',$order_date)->get();
        if($statistic){
            $statistic_count = $statistic->count();
        }else{
            $statistic_count = 0;
        }


        if ($bill->status_bill == 1) {
            //them
            $total_order = 0;
            $sales = 0;
            $profit = 0;
            $quantity = 0;

            foreach ($data['order_product_id'] as $key => $product_id) {
                $product = Product::find($product_id);
                $product_qty = $product->product_quantity;
                $product_soid = $product->product_soid;

                $product_price = $product->unit_price;
                $now = Carbon::now('Asia/Ho_Chi_Minh')->toDateString();

                foreach ($data['quantity'] as $key2 => $qty) {
                    if ($key==$key2) {
                        $pro_remain = $product_qty - $qty;
                        $product->product_quantity = $pro_remain;
                        $product->product_soid = $product_soid + $qty;
                        $product->save();

                        //update doanh thu
                        $quantity+=$qty;
                        $total_order+=1;
                        $sales+=$product_price*$qty;
                        $profit = $sales - 1000;
                    }
                }
            }
            //update doanh so db
            if($statistic_count > 0){
                $statistic_update = Statistical::where('order_date',$order_date)->first();
                $statistic_update->sales = $statistic_update->sales + $sales;
                $statistic_update->profit =  $statistic_update->profit + $profit;
                $statistic_update->quantity =  $statistic_update->quantity + $quantity;
                $statistic_update->total_order = $statistic_update->total_order + $total_order;
                $statistic_update->save();

            }else{

                $statistic_new = new Statistical();
                $statistic_new->order_date = $order_date;
                $statistic_new->sales = $sales;
                $statistic_new->profit =  $profit;
                $statistic_new->quantity =  $quantity;
                $statistic_new->total_order = $total_order;
                $statistic_new->save();
            }
        }else if ($bill->status_bill == 0 || $bill->status_bill == 2) {
            foreach ($data['order_product_id'] as $key => $product_id) {
                $product = Product::find($product_id);
                $product_qty = $product->product_quantity;
                $product_soid = $product->product_soid;

                if ($product->product_soid !=0) {
                    foreach ($data['quantity'] as $key2 => $qty) {
                        if ($key==$key2) {
                            $pro_remain = $product_qty + $qty;
                            $product->product_quantity = $pro_remain;
                            $product->product_soid = $product_soid - $qty;
                            $product->save();
                        }
                    }
                }
            }
        }

    }

    public function print_order($checkout_code){
        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($this->print_order_convert($checkout_code));
        return $pdf->stream();
    }
    public function print_order_convert($checkout_code){

        $billdetaill_print = BillDetail::where('order_code',$checkout_code)->join('post', 'post.id_post', 'bill_detail.id_post_bill_detail')->get();
        $bill_print = Bill::where('order_code',$checkout_code)->get();

        foreach($billdetaill_print as $key => $bd){
            $namepro = $bd->sp_vi;
        }

        $day = date('d');
        $month = date('m');
        $year = date('Y');

        $kh_print = Bill::join('customer', 'customer.id', '=', 'bills.id_customer')->where('order_code',$checkout_code)->first();

        $date_order_create = date_create($kh_print->date_order);
        if ($kh_print->payment == 'ATM') {
            $kq_pay = 'Chuyển khoản';
        }else{
            $kq_pay = 'Tiền mặt';
        }

        $tonghop = "$namepro - $kh_print->order_code";
        $output = '';
        $soid = 1;


        $output.='
        <meta charset="UTF-8">
        <div style="width:100%; float:left; margin: 40px 0px;font-family: DejaVu Sans; line-height: 200%; font-size:12px">
        <p style="float: right; text-align: right; padding-right:20px; line-height: 140%">
          Ngày đặt hàng: '.date_format($date_order_create, "d-m-Y").'<br><br>
          <span text-align: center>'.DNS2D :: getBarcodeHTML ( $tonghop, 'QRCODE',6.5,5).' </span>
        </p>
        <div style="float: left; margin: 0 0 1.5em 0; ">
         <strong style="font-size: 18px;">PhongVu</strong>
          <br />
          <strong>Địa chỉ:</strong> 1XX Bình Dương, TDM.
          <br/>
          <strong>Điện thoại:</strong> 0773654031
          <br/>
          <strong>Website:</strong> PhongVu.demo
          <br/>
          <strong>Email:</strong> npn0208@gmail.com
        </div>
        <div style="clear:both"></div>
        <table style="width: 100%"><tr><td valign="top" style="width: 65%">
        <h3 style="font-size: 14px;margin: 1.5em 0 1em 0;">Chi tiết đơn hàng</h3>
        <hr style="border: none; border-top: 2px solid #0975BD;"/>

        <table style="margin: 0 0 1.5em 0;font-size: 12px;" width="100%">
          <thead>
            <tr>
              <th style="width:25%;text-align: left;padding: 5px 0px">STT</th>
              <th style="width:35%;text-align: left;padding: 5px 0px">Sản phẩm</th>
              <th style="width:15%;text-align: right;padding: 5px 0px">Số lượng</th>
              <th style="width:25%;text-align: right;padding: 5px 0px">Giá</th>
            </tr>
          </thead>
          <tbody>';
            foreach($billdetaill_print as $key => $bd){
                foreach($bill_print as $key2 => $b_print){
                    if ($kh_print->payment == 'ATM') {
                        # code...
                        $toto = 0;
                    }else{
                        $toto = number_format($bd->unit_price,0,',','.');
                    }
                    $output.='
                    <tr valign="top" style="border-top: 1px solid #d9d9d9;">
                      <td align="left" style="padding: 5px 0px">'.$soid++.'</td>
                      <td align="left" style="padding: 5px 5px 5px 0px;white-space: pre-line;">'.$bd->sp_vi.'</td>
                      <td align="center" style="padding: 5px 0px">'.$bd->quantity.'</td>
                      <td align="right" style="padding: 5px 0px">'.number_format($bd->unit_price,0,',','.').'</td>
                    </tr>';
                }
            }
            $output.='
          </tbody>
        </table>
        <h3 style="font-size: 14px;margin: 0 0 1em 0;">Thông tin thanh toán</h3>
        <table style="font-size: 12px;width: 100%; margin: 0 0 1.5em 0;">
          <tr>
            <td style="padding: 5px 0px">Tổng giá sản phẩm:</td>
            <td style="text-align:right">'.number_format($b_print->total,0,',','.').'</td>
          </tr>
          <tr>
              <td style="width: 50%;padding: 5px 0px">Phí vận chuyển:</td>
              <td style="text-align:right;padding: 5px 0px">0</td>
            </tr>
          <tr>
            <td style="padding: 5px 0px"><strong>Tổng tiền:</strong></td>
            <td style="text-align:right;padding: 5px 0px"><strong><p>'.$toto.' VNĐ</td>
          </tr>
        </table>
        <h3 style="font-size: 14px;margin: 0 0 1em 0;">Ghi chú:</h3>
        <p style="line-height: 30px">'.$kh_print->note.'</p>
        </td><td valign="top" style="padding: 0px 20px">
         <h3 style="font-size: 14px;margin: 1.5em 0 1em 0;">Thông tin đơn hàng</h3>
        <hr style="border: none; border-top: 2px solid #0975BD;"/>
          <div style="margin: 0 0 1em 0; padding: 1em; border: 1px solid #d9d9d9;">
            <strong>Mã đơn hàng:</strong><br>#'.$kh_print->order_code.'<br>
              <strong>Ngày đặt hàng:</strong><br>'.date_format($date_order_create, "d-m-Y").'<br>
            <strong>Phương thức thanh toán</strong><br>'.$kq_pay.'
            <br>
            <strong>Phương thức vận chuyển</strong><br>Shipper
          </div>
          <h3 style="font-size: 14px;margin: 1.5em 0 1em 0;">Thông tin mua hàng</h3>
        <hr style="border: none; border-top: 2px solid #0975BD;"/>
          <div style="margin: 0 0 1em 0; padding: 1em; border: 1px solid #d9d9d9;  white-space: normal;">
            <strong>'.$kh_print->name.'</strong><br/>
            '.$kh_print->address.'<br/>
            Điện thoại: '.$kh_print->phone_number.'<br/>
            Email:'.$kh_print->email.'
          </div>
        </td></tr></table><br/><br/><br/><p>Nếu bạn có thắc mắc, vui lòng liên hệ chúng tôi qua email <u>npn0208@gmail.com</u> hoặc 0773654031</p></div>
        ';

        return $output;


    }
}
