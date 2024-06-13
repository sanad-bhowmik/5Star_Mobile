@extends('layouts.front')
@section('content')
<div class="preloader" id="preloader2" style="background: url({{asset('assets/images/'.$gs->loader)}}) no-repeat scroll center center #FFF;"></div>
<div class="breadcrumb-area">
        <div class="container">
                <div class="row">
                        <div class="col-lg-12">
                                <ul class="pages">
                                        <li>
                                                <a href="{{ route('front.index') }}">
                                                        {{ $langg->lang17 }}
                                                </a>
                                        </li>
                                        <li>
                                                <a href="{{ route('front.customerservice') }}">
                                                        Customer Service
                                                </a>
                                        </li>
                                </ul>
                        </div>
                </div>
        </div>
</div>



<div class="container" style="margin-top: 50px; margin-bottom:50px;">

        <div class="">

                <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-4">

                                <form class="" id="customerservice">


                                        <div class="form-group">
                                                <label for="jobcode">Job Code: </label>
                                                <input type="text" class="form-control" placeholder="job code" id="jobcode">
                                        </div>
                                        OR
                                        <div class="form-group">
                                                <label for="rcvcode">Receiving Code:</label>
                                                <input type="text" class="form-control" placeholder="receive code" id="rcvcode">
                                        </div>

                                        <button type="submit" class="btn btn-primary">Search</button>
                                </form>



                        </div>
                        <div class="col-md-4"></div>
                </div>

        </div>







</div>

<div class="container" style="margin-bottom: 200px;">
        <div id="ajaxContent">

        </div>

</div>




@endsection




@section('scripts')
<script type="text/javascript">


$(window).on("load", function (e) {
        setTimeout(function(){
            $('#preloader2').fadeOut(500);
          },100)
      });


        $("#customerservice").submit(function(e) {
                e.preventDefault();

             //   $("#jobcode").val()
             //   $("#rcvcode").val()
             $('#preloader2').show();

                load_service_status();









        });


        function load_service_status() {
                link = '';
link += '{{route('front.servicestatus')}}' + '?jobCode=' + $("#jobcode").val() + '&receivingCode=' + $("#rcvcode").val();
                $("#ajaxContent").load(encodeURI(link), function(data) {
                        // add query string to pagination
                        //  addToPagination();
                        //console.log(data);
                        $('#preloader2').fadeOut(500);
                });



        }
</script>



@endsection