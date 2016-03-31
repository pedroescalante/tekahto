@extends('layouts.master')

<?php
  $status=['unknown', 'error', 'deleted', 'OK', 'Inactive'];
?>

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
            <tr>
              <th> Last 4 Numbers </th>
              <th> Status </th>
            </tr>
            @foreach($contact['CreditCards'] as $card)
            <tr>
              <td> {{$card['Last4']}}         </td>
              <td> {{$card['Status']}} = {{ $status[$card['Status']]}} </td>
            </tr>
            @endforeach
          </table>

          <h4> Recurring Orders </h4>
          @foreach($contact['Recs'] as $rec)
            <div>Recurring Order Id: <b> {{$rec['Id']}} </b> </th></div>
            <div> merchantAccountID <b> {{$rec['merchantAccountId']}} </b> </div>
            <div> Product Id <b> {{$rec['ProductId']}} <b> </div>
            <div> Start Date <b> {{$rec['StartDate']->format('Y-m-d')}} <b> </div>
          @endforeach

          <h4> Jobs </h4>
          @foreach($contact['Jobs'] as $job)
            <div>Job Id: <b> {{$job['Id']}} </b> </th></div>
            <div> Job Title <b> {{$job['JobTitle']}} </b> </div>
            <div> Product Id <b> {{$job['ProductId']}} <b> </div>
            
            <table class="table table-condensed">
              <tr>
                <th> Invoice Id   </th>
                <th> Description  </th>
                <th> Type         </th>
                <th> PayStatus    </th>
                <th> Total        </th>
                <th> Due          </th>
                <th> Paid         </th>
              </tr>
              @foreach($job['invoices'] as $invoice)
              <tr>
                <td> {{$invoice['Id']}}             </td>
                <td> {{$invoice['Description']}}    </td>
                <td> <?php if( isset($invoice['InvoiceType']) )  echo $invoice['InvoiceType']; ?>    </td>
                <td> {{$invoice['PayStatus']}}      </td>
                <td> {{$invoice['InvoiceTotal']}}   </td>
                <td> {{$invoice['TotalDue']}}       </td>
                <td> {{$invoice['TotalPaid']}}      </td>
              </tr>
              @endforeach
            </table>
            <hr>
          @endforeach

        </div>
@stop      
