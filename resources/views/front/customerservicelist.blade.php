
<div style="margin-bottom: 200px;">
<h5> Service List </h5>
<table class="table table-responsive table-striped">
<thead>
        <th>Job Code</th>
        <th>Customer Name</th>
        <th>Customer Type</th>
        
        <th>Courier Name</th>
        <th>Courier Number</th>
        <th>Probably Date</th>
        <th>Status</th>
</thead>
<tbody>
        @forelse($lists as $list )
        @php 
        $class= $list['StateStatus']=='Delivery-Done' ? 'table-success' :'table-warning'; 
        @endphp
        <tr class="{{$class}}">
        <td> {{ $list['JobOrderCode']}} </td>
        <td> {{ $list['CustomerName']}} </td>
        <td> {{ $list['CustomerType']}} </td>
        <td> {{ $list['CourierName']}} </td>
        <td> {{ $list['CourierNumber']}} </td>
        
        <td> {{ $list['ProbablyDate']}} </td>

      
        <td >{{ $list['StateStatus']}}</td>

        </tr>
        @empty

        <h3>Nothing Found</h3>

        @endforelse

</tbody>


</table>

</div>