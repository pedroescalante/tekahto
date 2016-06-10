@extends('layouts.master')

@section('content')
        <div class="col-sm-12">
            <h4>Contacts</h4>
            <table class="table table-striped">
                <tr>
                    <th> ID                 </th>
                    <th> Name               </th>
                    <th> Email              </th>
                    <th> Subscription Plans </th>
                </tr>
                @foreach($contacts as $contact)
                <tr>
                    <td> {{$contact['ID']}}         </td>
                    <td> {{$contact['FirstName']}} {{$contact['LastName']}}  </td>
                    <td> {{$contact['Email']}} </td>
                    <td> 
                    @foreach($contact['subscriptions'] as $sub)
                        <li> 
                            <?php if( isset($sub['ProductName'])) echo $sub['ProductName'] ?>
                            <?php if( isset($sub['Status'])){
                                if( $sub['Status']=="Active") 
                                    echo "<span class='label label-success'>".$sub['Status']."</span>";
                                else
                                    echo $sub['Status'];
                                }
                            ?>
                        </li>
                    </td>
            </tr>
                @endforeach
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
@stop      
