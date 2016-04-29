@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Subscription Plans</h4>
          <table class="table table-striped">
            <tr>
              <th> Id         </th>
              <th> Product </th>
		<th> Plan Price </th>
              <th> Number of Cycles </th>
		<th> Frequency </th>
              <th> Active </th>
            </tr>
            @foreach($subscription_plans as $plan)
            <tr>
              	<td> {{ $plan['Id'] }} </td>
              	<td> <?php if( isset($products[$plan['ProductId']]['ProductName'])) echo $products[$plan['ProductId']]['ProductName']; else echo "-"; ?>  </td>
		<td> {{ $plan['PlanPrice'] }} </td>
		<td> <?php if( isset( $plan['Number of Cycles'])) echo $plan['NumberOfCycles']; else echo "-"; ?> </td>
		<td> {{ $plan['Frequency'] }} </td>
		<td> {{ $plan['Active'] }} </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      
