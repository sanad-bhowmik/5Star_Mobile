@extends('layouts.admin') 

@section('content')  
					<input type="hidden" id="headerdata" value="{{ __('BONUS') }}">
					<div class="content-area">
						<div class="mr-breadcrumb">
							<div class="row">
								<div class="col-lg-12">
										<h4 class="heading">{{ __('Registration Bonus') }}</h4>
										<ul class="links">
											<li>
												<a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
											</li>
											<li>
												<a href="{{ route('admin-user-index') }}">{{ __('Customers') }}</a>
											</li>
											<li>
												<a href="{{ route('admin-bonus') }}">{{ __('Registation Bonus') }}</a>
											</li>
										</ul>
								</div>
							</div>
						</div>
						<div class="product-area">
							<div class="row">
								<div class="col-lg-12">
									<div class="mr-table allproduct">
										@include('includes.admin.form-success') 
										<div class="table-responsiv">
												<table id="dastable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
													<thead>
														<tr>
									                        <th>{{ __('Registration Bonus') }}</th>
									                     
									                        <th>{{ __('Options') }}</th>
														</tr>
													</thead>
													<tbody>
													<tr>
													<td>{{ $data->bonus }} TK</td>
													<td>{{ $data->id }}</td>
													</tr>
													
													
													</tbody>
												</table>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

{{-- ADD / EDIT MODAL --}}

				<div class="modal fade" id="modal1" tabindex="-1" role="dialog" aria-labelledby="modal1" aria-hidden="true">
										
					<div class="modal-dialog modal-dialog-centered" role="document">
						<div class="modal-content">
							<div class="submit-loader">
								<img  src="{{asset('assets/images/'.$gs->admin_loader)}}" alt="">
							</div>
							<div class="modal-header">
								<h5 class="modal-title"></h5>
								<button type="button" class="close" data-dismiss="modal" aria-label="Close">
									<span aria-hidden="true">&times;</span>
								</button>
							</div>
							<div class="modal-body">

							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
							</div>
						</div>
					</div>

				</div>

{{-- ADD / EDIT MODAL ENDS --}}



@endsection    



@section('scripts')

{{-- DATA TABLE --}}


 

{{-- DATA TABLE --}}
    
@endsection   