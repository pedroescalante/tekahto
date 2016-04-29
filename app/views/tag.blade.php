@extends('layouts.master')
@section('content')
      @foreach($tags as $tag)
        <div class="col-sm-12">
          <h4>Tag</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <td> {{ $tag['Id'] }} </td>
            </tr>
            <tr>
              <th> Name         </th>
              <td> {{ $tag['GroupName'] }} </td>
            </tr>
            <tr>
              <th> Description         </th>
              <td> {{ $tag['Description'] }} </td>
            </tr>
            <tr>
              <th> Category </th>
              <td> {{ @$tag['GroupCategoryId'] }} </td>
            </tr>
            <tr>
              <th> Group Description </th>
              <td> <?php /*if isset($tag['GroupDescription']) echo $tag['GroupDescription']*/ ?> </td>
            </tr>
          </table>
        </div>
      @endforeach
@stop      
