@extends('Layout')
@section('title')    
	{{ trans('home.contact') }}
@endsection
@section('content-layout')
<!-- Breadcrumb Start -->
<div class="breadcrumb-area mt-30">
    <div class="container">
        <div class="breadcrumb">
            <ul class="d-flex align-items-center">
                <li><a href="{{route('trang-chu')}}">{{ trans('home.home') }}</a></li>
                <li class="active"><a href="{{route('lienhe')}}">{{ trans('home.contact') }}</a></li>
            </ul>
        </div>
    </div>
    <!-- Container End -->
</div>
<!-- Breadcrumb End -->
<!-- Contact Email Area Start -->
<div class="contact-area ptb-100 ptb-sm-60">
    <div class="container">
        <h3 class="mb-20">{{ trans('home.Contact_Form') }}</h3>
        <p class="text-capitalize mb-20"></p><br>
        <form id="contact-form" class="contact-form" action="{{route('lienhe')}}" method="post">
        	<input type="hidden" name="_token" value="{{csrf_token()}}">
		    @if(Session::has('thongbao'))
		        <div style="height: 50px" class="alert alert-success form-control" style="width: 100%">{{Session::get('thongbao')}}</div> 
		    @endif
            <div class="address-wrapper row">
                <div class="col-md-12">
                    <div class="address-fname">
                        <input class="form-control" type="text" name="name" placeholder="{{ trans('home.YourName') }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="address-email">
                        <input class="form-control" type="email" name="email" placeholder="Email">
                    </div>
                </div>
<!--                 <div class="col-md-6">
                    <div class="address-web">
                        <input class="form-control" type="text" name="website" placeholder="{{$url_canonical}}" readonly>
                    </div>
                </div> -->
                <div class="col-md-12">
                    <div class="address-textarea">
                        <textarea name="content" class="form-control" placeholder="{{ trans('home.Writeyourmessage') }}"></textarea>
                    </div>
                </div>
            </div>
            <p class="form-message"></p>
            <div class="footer-content mail-content clearfix">
                <div class="send-email float-md-right">
                    <input value="{{ trans('home.send') }}" class="return-customer-btn" type="submit">
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Contact Email Area End -->
<!-- Google Map Start -->
<div class="goole-map">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d30672.66285360389!2d108.178100815625!3d16.0611896!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31421955ddf3d6ad%3A0x9cac05a049063f3!2zTcOBWSBUw41OSCBQSE9ORyBWxaggxJDDgCBO4bq0Tkc!5e0!3m2!1svi!2s!4v1677328812049!5m2!1svi!2s" id="map" style="height:500px; width: 100%"></iframe>
</div>
<!-- Google Map End --> 
@endsection