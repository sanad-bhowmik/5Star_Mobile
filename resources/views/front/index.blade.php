@extends('layouts.front')

@section('content')

	@if($ps->slider == 1)

		@if(count($sliders))

			@include('includes.slider-style')
		@endif
	@endif

	@if($ps->slider == 1)
			<!-- Hero Area Start -->
			<section class="hero-area">
			<div class="my-container">
				<div class="row">
				
					<div class="col-lg-12 decrease-padding">
							@if($ps->slider == 1)
							@if(count($sliders))
								<div class="hero-area-slider">
									<div class="slide-progress"></div>
									<div class="intro-carousel">
										@foreach($sliders as $data)
											<div class="intro-content {{$data->position}} lazy" data-src="{{asset('assets/images/sliders/'.$data->photo)}}" >
												<div class="slider-content">
													<!-- layer 1 -->
													<div class="layer-1">
														<h4 style="font-size: {{$data->subtitle_size}}px; color: {{$data->subtitle_color}}" class="subtitle subtitle{{$data->id}}" data-animation="animated {{$data->subtitle_anime}}">{{$data->subtitle_text}}</h4>
														<h2 style="font-size: {{$data->title_size}}px; color: {{$data->title_color}}" class="title title{{$data->id}}" data-animation="animated {{$data->title_anime}}">{{$data->title_text}}</h2>
													</div>
													<!-- layer 2 -->
													<div class="layer-2">
														<p style="font-size: {{$data->details_size}}px; color: {{$data->details_color}}"  class="text text{{$data->id}}" data-animation="animated {{$data->details_anime}}">{{$data->details_text}}</p>
													</div>
													<!-- layer 3 -->
													<div class="layer-3">
														<a href="{{$data->link}}" target="_blank" class="mybtn1"><span>{{ $langg->lang25 }} <i class="fas fa-chevron-right"></i></span></a>
													</div>
												</div>
											</div>
										@endforeach
									</div>
								</div>
							@endif
						@endif
					</div>
				</div>	
			</div>
		</section>
		<!-- Hero Area End -->
	
	@endif


	@if($ps->featured_category == 1)

	{{-- Slider Bottom Banner Series Start --}}
	<section class="slider_bottom_banner mt-10">

	<div class="row">
				<div class="col-lg-12">
						<div class="flash-deals">
							<div class="series_slider">							
			@foreach(DB::table('featured_banners')->get()->chunk(4) as $data1)
			
				@foreach($data1 as $data)
				
				<a href="{{ $data->link }}" target="_blank" class="banner-effect">
				
			<img   src="{{ $data->photo ? asset('assets/images/featuredbanner/'.$data->photo) : asset('assets/images/noimage.png') }}" alt="">
				</a>
				
				@endforeach

		    @endforeach

							</div>
						</div>
					</div>
				</div>



		
	</section>
	{{-- Slider Botom Banner End --}}

	@endif

	
		

	<section class="">						
			
		      
		@foreach($arrival as $data)		
		<section class="slider_arrival mt-10">

			<div class="container-my ">
				<h2 class="heading">NEW ARRIVAL</h2>

				@if($loop->index%2==0)
				<div class="row bg-pattern3">
							
							<div class="col-md-6 d-flex justify-content-center ">
								<div class="text-area">
									<h3>{{$data->name}}</h3>
									<p>{{ $data->short_description }}</p>
									<a href="{{route('front.product',$data->slug)}}" class="btn btn-danger">Read More</a>
								</div>
							</div>

							<div class="col-md-6 d-flex justify-content-center">
							 <div class="image-holder">
							   <a href="{{route('front.product',$data->slug)}}"><img class="img-fluid" src="{{ $data->photo ? asset('assets/images/products/'.$data->photo) : asset('assets/images/noimage.png') }}" alt="image-description">
								</a>
							 </div>
							</div>
						</div>

				@else

				<div class="row bg-pattern2">
						
				
				<div class="col-md-6 d-flex justify-content-center">
							 <div class="image-holder">
							   <a href="{{route('front.product',$data->slug)}}"><img class="img-fluid" src="{{ $data->photo ? asset('assets/images/products/'.$data->photo) : asset('assets/images/noimage.png') }}" alt="image-description">
								</a>
							 </div>
							</div>
							
							<div class="col-md-6 d-flex justify-content-center ">
								<div class="text-area">
									<h3>{{$data->name}}</h3>
									<p>{{ $data->short_description }}</p>
									<a href="{{route('front.product',$data->slug)}}" class="btn btn-danger">Read More</a>
								</div>
							</div>

							
						</div>
				@endif
						

							
			
			</div>
				
						
				
		</section>
				
				@endforeach
			  
			

@if($ps->service == 1)

{{-- Info Area Start --}}
<section class="info-area mt-15">
		<div class="container-my">
	
			@foreach($services->chunk(4) as $chunk)
	
			<div class="row">
	
				<div class="col-lg-12 p-0">
					<div class="info-big-box">
						<div class="row">
							@foreach($chunk as $service)
							<div class="col-6 col-xl-3 p-0">
								<div class="info-box">
									<div class="icon">
										<img src="{{ asset('assets/images/services/'.$service->photo) }}">
									</div>
									<div class="info">
										<div class="details">
											<h4 class="title">{{ $service->title }}</h4>
											<p class="text">
												{!! $service->details !!}
											</p>
										</div>
									</div>
								</div>
							</div>
							@endforeach
						</div>
					</div>
				</div>
	
			</div>
	
			@endforeach
	
		</div>
	</section>
	{{-- Info Area End  --}}


@endif	



	@if($ps->featured == 1)
		<!-- Trending Item Area Start -->
		<section  class="trending">
			<div class="container-my">
				<div class="row">
					<div class="col-lg-12 remove-padding">
						<div class="section-top">
							<h2 class="section-title">
								{{ $langg->lang26 }}
							</h2>
							{{-- <a href="#" class="link">View All</a> --}}
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-lg-12 remove-padding">
						<div class="trending-item-slider">
							@foreach($feature_products as $prod)
								@include('includes.product.slider-product')
							@endforeach
						</div>
					</div>

				</div>
			</div>
		</section>
		<!-- Tranding Item Area End -->
	@endif

	@if($ps->small_banner == 1)
		<!-- Banner Area One Start -->
		<section class="banner-section">
			<div class="container-my">
				@foreach($top_small_banners->chunk(2) as $chunk)
					<div class="row">
						@foreach($chunk as $img)
							<div class="col-lg-6 remove-padding">
								<div class="left">
									<a class="banner-effect" href="{{ $img->link }}" target="_blank">
										<img src="{{asset('assets/images/banners/'.$img->photo)}}" alt="">
									</a>
								</div>
							</div>
						@endforeach
					</div>
				@endforeach
			</div>
		</section>
		<!-- Banner Area One Start -->
	@endif

	<section id="extraData">
		<div class="text-center">
		<img class="{{ $gs->is_loader == 1 ? '' : 'd-none' }}" src="{{asset('assets/images/'.$gs->loader)}}">
		</div>
	</section>
	</section>

@endsection

@section('scripts')
<script type="text/javascript" src="{{asset('assets/front/js/lazy.min.js')}}"></script>
<script type="text/javascript" src="{{asset('assets/front/js/lazy.plugin.js')}}"></script>

	<script>
        	function lazy (){
			$(".lazy").Lazy({
				scrollDirection: 'vertical',
				effect: 'fadeIn',
				visibleOnly: true,
				onError: function(element) {
					console.log('error loading ' + element.data('src'));
				}
			});
		}

        $(window).on('load',function() {
			
            setTimeout(function(){
                $('#extraData').load('{{route("front.extraIndex")}}');

            }, 500);

			 lazy();
        });


	</script>
@endsection