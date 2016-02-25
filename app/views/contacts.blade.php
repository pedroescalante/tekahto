@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Contacts</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <th> First Name </th>
              <th> Last Name  </th>
              <th> Email      </th>
            </tr>
            @foreach($contacts as $contact)
            <tr>
              <td> {{$contact['ID']}}         </td>
              <td> {{$contact['FirstName']}}  </td>
              <td> {{$contact['LastName']}}   </td>
              <td> {{$contact['Email']}}      </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      