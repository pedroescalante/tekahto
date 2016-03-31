@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
          <h4>Contacts</h4>
          <table class="table table-striped">
            <tr>
              <th> ID         </th>
              <th> Name       </th>
              <th> Email      </th>
	      <th> Registered CC </th>
            </tr>
            @foreach($contacts as $contact)
            <tr>
              <td> {{$contact['ID']}}         </td>
              <td> {{$contact['FirstName']}} {{$contact['LastName']}}  </td>
              <td> 
                <a href="/infusionsoft/contact?email={{$contact['Email']}}" class="btn btn-primary">
                  {{$contact['Email']}}      
                </a>
              </td>
	      <td>
		{{ count($contact['CreditCards']) }}
            </tr>
            @endforeach
          </table>
        </div>
@stop      
