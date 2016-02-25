@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Products</h4>
          <table class="table table-striped">
            <tr>
              <th> Id         </th>
              <th> Name </th>
              <th> Description      </th>
              <th> Price </th>
              <th> Status </th>
            </tr>
            @foreach($products as $product)
            <tr>
              <td> {{$product['Id']}}         </td>
              <td> {{$product['Name']}}  </td>
              <td> {{$product['Description']}}  </td>
              <td> {{$product['Price']}}  </td>
              <td> {{$product['Status']}}  </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      