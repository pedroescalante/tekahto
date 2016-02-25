@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Contact</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $c['Id'] }} </td>
            </tr>
            <tr>
              <th> First Name         </th>
              <td> {{ $c['FirstName'] }} </td>
            </tr>
            <tr>
              <th> Last Name         </th>
              <td> {{ $c['LastName'] }} </td>
            </tr>
            <tr>
              <th> Registered Credit Cards </th>
              <td> {{ count($c['CreditCards']) }} </td>
            </tr>
            </tr>
            @foreach($c['CreditCards'] as $card)
            <tr>
              <td> {{$card['Last4']}}         </td>
              <td> {{$card['Status']}}  </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      