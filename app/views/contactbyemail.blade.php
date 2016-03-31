@extends('layouts.master')

<?php
  $status=['Unknown', 'Error', 'Deleted', 'OK', 'Inactive'];
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
              <th> Type </th>
              <th> Status </th>
            </tr>
            @foreach($contact['CreditCards'] as $card)
            <tr>
              <td> {{$card['Last4']}}         </td>
              <td> {{$card['CardType']}}         </td>
              <td> {{$card['Status']}} = {{ $status[$card['Status']]}} </td>
            </tr>
            @endforeach
          </table>

	  <div class="panel panel-default">
  <div class="panel-heading">Recurring Orders</div>
  <div class="panel-body">
    @foreach($contact['Recs'] as $rec)
            <div>Recurring Order Id: <b> {{$rec['Id']}} </b> </th></div>
            <div> Merchant Account ID <b> {{$rec['merchantAccountId']}} </b> </div>
            <div> Product Name <b> {{$rec['ProductName']}} </b> </div>
            <div> Start Date <b> {{$rec['StartDate']->format('Y-m-d')}} </b> </div>
            <div> End Date <b> <?php if(isset($rec['EndDate'])) echo $rec['StartDate']->format('Y-m-d'); else echo "-"; ?> </b> </div>
            <hr>
          @endforeach
  </div>
</div>

          <h4> Jobs </h4>
          @foreach($contact['Jobs'] as $job)
            <div>Job Id: <b> {{$job['Id']}} </b> </th></div>
            <div> Job Title <b> {{$job['JobTitle']}} </b> </div>
            <div> Product Id <b> {{$job['ProductId']}} </b> </div>
            
<h5>Invoices</h5>
            <table class="table table-condensed table-bordered">
              <tr class="info">
                <th> Id   </th>
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
