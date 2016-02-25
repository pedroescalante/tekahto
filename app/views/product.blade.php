@extends('layouts.master')
<?php var_dump($product); ?>
@section('content')
        <div class="col-sm-12">
          <h4>Product</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $product['Id'] }} </td>
            </tr>
            <tr>
              <th> Name         </th>
              <td> {{ $product['product_name'] }} </td>
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