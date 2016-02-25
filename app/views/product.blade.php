@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Prouct</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $product['Id'] }} </td>
            </tr>
            <tr>
              <th> Name         </th>
              <td> {{ $product['ProductName'] }} </td>
            </tr>
            <tr>
              <th> Price         </th>
              <td> {{ $product['ProductPrice'] }} </td>
            </tr>
            <tr>
              <th> Status </th>
              <td> {{ $product['Status'] }} </td>
            </tr>
          </table>
        </div>
@stop      