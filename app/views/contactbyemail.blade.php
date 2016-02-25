@extends('layouts.master')

@$status=['unknown', 'error', 'deleted', 'OK, 'Inactive']

@section('content')
        <div class="col-sm-12">
          <h4>Contact</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $contact['Id'] }} </td>
            </tr>
            <tr>
              <th> First Name         </th>
              <td> {{ $contact['FirstName'] }} </td>
            </tr>
            <tr>
              <th> Last Name         </th>
              <td> {{ $contact['LastName'] }} </td>
            </tr>
            <tr>
              <th> Registered Credit Cards </th>
              <td> {{ count($contact['CreditCards']) }} </td>
            </tr>
          </table>
          <h4> Credit Cards </h4>
          <table class="table table-striped">
            </tr>
            @foreach($contact['CreditCards'] as $card)
            <tr>
              <td> {{$card['Last4']}}         </td>
              <td> {{$card['Status']}} = {{ $status[$card['Status']]}} </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      