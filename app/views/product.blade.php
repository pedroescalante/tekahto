@extends('layouts.master')
@section('content')
        <div class="col-sm-12">
          <h4>Product</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $product['id'] }} </td>
            </tr>
            <tr>
              <th> Name         </th>
              <td> {{ $product['product_name'] }} </td>
            </tr>
            <tr>
              <th> Price         </th>
              <td> {{ $product['product_price'] }} </td>
            </tr>
            <tr>
              <th> Status </th>
              <td> {{ $product['status'] }} </td>
            </tr>
            <tr>
              <th> Description </th>
              <td> {{ $product['product_short_desc'] }} </td>
            </tr>
          </table>
        </div>
@stop      