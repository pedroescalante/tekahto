@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Contacts</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <th> First Name </th>
              <th> Email      </th>
            </tr>
            @foreach($contacts as $contact)
            <tr>
              <td> {{$contact['ID']}}         </td>
              <td> {{$contact['FirstName']}}  </td>
              <td> 
                <a href="/contacts/byemail?email={{$contact['Email']}}" class="btn btn-primary">
                  {{$contact['Email']}}      
                </a>
              </td>
            </tr>
            @endforeach
          </table>
        </div>
@stop      