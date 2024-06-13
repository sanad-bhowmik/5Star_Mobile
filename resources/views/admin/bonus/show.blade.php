@extends('layouts.load')
@section('content')

						<div class="content-area no-padding">
							<div class="add-product-content">
								<div class="row">
									<div class="col-lg-12">
										<div class="product-description">
											<div class="body-area" id="modalEdit">

                                    <div class="table-responsive show-table">
                                        <table class="table">
                                            <tr>
                                                <th width="50%">{{ __("Registration Bonus") }}</th>
                                                <td>{{ $data->bonus }}</td>
                                            </tr>
                                            
                                            
                                        </table>
                                    </div>


											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

@endsection