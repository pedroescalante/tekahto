<?php

	echo json_encode($products);

?>

/*
@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Products</h4>
          <table class="table table-striped">
            <tr>
              <th> Id         </th>
              <th> Name </th>
              <th> Price </th>
              <th> Status </th>
            </tr>
            @foreach($products as $product)
            <tr>
              <td>
                <a href="/infusionsoft/product?id={{$product['Id']}}"> 
                  {{$product['Id']}}
                </a>
              </td>
              <td> {{$product['ProductName']}}  </td>
              <td> {{$product['ProductPrice']}}  </td>
              <td> {{$product['Status']}}  </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      
*/
