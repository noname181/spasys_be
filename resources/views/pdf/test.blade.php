<!DOCTYPE html>
<html>
   <head>
      <title>Send Mail</title>
      <style>
         .page-break {
         page-break-after: always;
         }
         #custom_table{
         width:100%;
         border-collapse: collapse;
         }
         #custom_table td,#custom_table th{
         border: 1px solid #ddd;
         padding:8px;
         text-align: center;
         }
         .background_title{
            background-color: #f3f4fb !important;
            font-weight: bold;
         }
         /* #custom_table td{
         } */
      </style>
   </head>
   <body>
      <div style="width: 100%;border:1px solid black;margin-bottom:20px;text-align: center;padding: 50px 0;">
         <p>{{$rm_biz_name}}</p>
      </div>
      <div style="width: 100%;margin-bottom:20px;text-align: right;">
         <p>{{$rm_biz_number}}</p>
         <p>{{$rm_biz_address}}</p>
         @if($rm_owner_name)
         @if($rm_owner_name && !$rm_biz_email)
         <p>{{$rm_owner_name}}</p>
         @else
         <p>{{$rm_owner_name}} ({{$rm_biz_email}})</p>
         @endif
         @endif
      </div>
      @if(count($array1) > 0)
      @if(count($rate_data_send_meta['rate_data1']))
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr class="background_title">
                  <th style='font-family: "unbatang", Times, serif' class="background_title">구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif' class="background_title">내역</th>
               </tr>
            
            </thead>
            <tbody>
               @if($count1 > 0)
               <tr class="background_title">
                  <td rowspan="{{$count1+1}}"  style='font-family: "unbatang", Times, serif' >하역비용</td>
       
                  <td   style='font-family: "unbatang", Times, serif'>항목</td>
                  <td   style='font-family: "unbatang", Times, serif'>상세</td>
                  <td   style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td   style='font-family: "unbatang", Times, serif'>단가/KG</td>
            
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 15 && $key != 4 && $key !=2 && $key !=6 && $key !=10 && $key !=8 && ($key == 0 || $key == 1 || $key == 3 || $key == 5 || $key == 7 || $key == 9) )
               @if(in_array($key,$array1))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 14 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endif
               @endforeach
               @endif   
               @if($count2 > 0)      
               <tr class="background_title">
                  <td rowspan="{{$count_row2}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>반출입</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td style='font-family: "unbatang", Times, serif'>단가/KG</td>
               </tr>
               <tr>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 11) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 14 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @endif
               @if($count2 == 0 && $count3 > 1)
               <tr class="background_title">
                  <td rowspan="{{$count_row2}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
               
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count2 > 0 && $count3 > 1)
               <tr class="background_title">
                 
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count3 == 1)
               <tr></tr>
               @endif
               @if($count3 > 1)
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 15 && $key != 4 && $key !=2 && $key !=6 && $key !=10 && $key !=8 && ($key == 12 || $key == 13 || $key == 14) )
               @if(in_array($key,$array1))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style=' border: 1px solid #ddd;font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  @if($key == 14)
                  <td style='font-family: "unbatang", Times, serif' colspan="2">{{$row->rd_data1}}</td>
                  @else
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_data1}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 14 ? $row->rd_data2 : ''}}</td>
                  @endif
               </tr>
               @endif
               @endif
               @endforeach
               @endif
            </tbody>
         </table>
      </div>
      @endif
      @endif
      @if(count($array2) > 0)
      @if(count($rate_data_send_meta['rate_data1']))
      @if(count($array1) > 0)
      <div class="page-break"></div>
      @endif
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr class="background_title">
                  <th style='font-family: "unbatang", Times, serif'>구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif'>내역</th>
               </tr>
            
            </thead>
            <tbody>
               @if($count1_2 > 0)
               <tr class="background_title">
                  <td rowspan="{{$count1_2+1}}"  style='font-family: "unbatang", Times, serif' >하역비용</td>
       
                  <td   style='font-family: "unbatang", Times, serif'>항목</td>
                  <td   style='font-family: "unbatang", Times, serif'>상세</td>
                  <td   style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td   style='font-family: "unbatang", Times, serif'>단가/KG</td>
            
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key != 19 && $key !=17 && $key !=21 && $key !=25 && $key !=23 && ($key == 15 || $key == 16 || $key == 18 || $key == 20 || $key == 22 || $key == 24) )
               @if(in_array($key,$array2))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 29 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endif
               @endforeach
               @endif   
               @if($count2_2 > 0)      
               <tr class="background_title">
                  <td rowspan="{{$count_row2_2}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>반출입</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td style='font-family: "unbatang", Times, serif'>단가/KG</td>
               </tr>
               <tr>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 26) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 29 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @endif
               @if($count2_2 == 0 && $count3_2 > 1)
               <tr class="background_title">
                  <td rowspan="{{$count_row2_2}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
               
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count2_2 > 0 && $count3_2 > 1)
               <tr class="background_title">
                 
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count3_2 == 1)
               <tr></tr>
               @endif
               @if($count3_2 > 1)
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if( $key != 19 && $key !=17 && $key !=21 && $key !=25 && $key !=23 && ($key == 27 || $key == 28 || $key == 29) )
               @if(in_array($key,$array2))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style=' border: 1px solid #ddd;font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  @if($key == 29)
                  <td style='font-family: "unbatang", Times, serif' colspan="2">{{$row->rd_data1}}</td>
                  @else
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_data1}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 29 ? $row->rd_data2 : ''}}</td>
                  @endif
               </tr>
               @endif
               @endif
               @endforeach
               @endif
            </tbody>
         </table>
      </div>
      @endif
      @endif
      @if(count($array3) > 0)
      @if(count($rate_data_send_meta['rate_data1']))
      @if(count($array1) > 0 || count($array2) > 0)
      <div class="page-break"></div>
      @endif
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr class="background_title">
                  <th style='font-family: "unbatang", Times, serif'>구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif'>내역</th>
               </tr>
            
            </thead>
            <tbody>
               @if($count1_3 > 0)
               <tr class="background_title">
                  <td rowspan="{{$count1_3+1}}"  style='font-family: "unbatang", Times, serif' >하역비용</td>
       
                  <td   style='font-family: "unbatang", Times, serif'>항목</td>
                  <td   style='font-family: "unbatang", Times, serif'>상세</td>
                  <td   style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td   style='font-family: "unbatang", Times, serif'>단가/KG</td>
            
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key != 34 && $key !=32 && $key !=36 && $key !=40 && $key !=38 && ($key == 30 || $key == 31 || $key == 33 || $key == 35 || $key == 37 || $key == 39) )
               @if(in_array($key,$array3))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 44 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endif
               @endforeach
               @endif   
               @if($count2_3 > 0)      
               <tr class="background_title">
                  <td rowspan="{{$count_row2_2}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>반출입</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료</td>
                  <td style='font-family: "unbatang", Times, serif'>단가/KG</td>
               </tr>
               <tr>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 41) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 44 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @endif
               @if($count2_3 == 0 && $count3_3 > 1)
               <tr class="background_title">
                  <td rowspan="{{$count_row2_3}}"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
               
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count2_3 > 0 && $count3_3 > 1)
               <tr class="background_title">
                 
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>보관</td>
                 
                  <td style='font-family: "unbatang", Times, serif'>기본료율</td>
                  <td style='font-family: "unbatang", Times, serif'>할증료율(24시간 경과)</td>
             
               </tr>
               @elseif($count3_3 == 1)
               <tr></tr>
               @endif
               @if($count3_3 > 1)
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if( $key != 34 && $key !=32 && $key !=36 && $key !=40 && $key !=38 && ($key == 42 || $key == 43 || $key == 44) )
               @if(in_array($key,$array3))
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td colspan="2" style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <!-- <td style=' border: 1px solid #ddd;font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td> -->
                  @if($key == 44)
                  <td style='font-family: "unbatang", Times, serif' colspan="2">{{$row->rd_data1}}</td>
                  @else
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_data1}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 44 ? $row->rd_data2 : ''}}</td>
                  @endif
               </tr>
               @endif
               @endif
               @endforeach
               @endif
            </tbody>
         </table>
      </div>
      @endif
      @endif
     
      <!-- @if(count($rate_data_send_meta['rate_data1']))
         @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
         @if($key >= 15 && $key < 30)
         <div>
             <span style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</span>
             <span style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</span>
             <span style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</span>
             <span style='font-family: "unbatang", Times, serif'>{{$row->rd_data1}}</span>
             <span style='font-family: "unbatang", Times, serif'>{{$row->rd_data2}}</span>
         </div>
         @endif
         @endforeach
         @endif -->
      @if(count($rate_data_send_meta['rate_data2']))
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr  class="background_title"> 
                  <th colspan="2"  style='font-family: "unbatang", Times, serif'>기준</th>
                  <th   style='font-family: "unbatang", Times, serif'>단위</th>
                  <th   style='font-family: "unbatang", Times, serif'>단가</th>
                  <th   style='font-family: "unbatang", Times, serif'>ON/OFF</th>
               </tr>
            </thead>
            <tbody>
               <!-- <tr
                  className={`nl-hidden ${
                    rateData?.[0]?.rd_data3 === "OFF" ? "h-bg-f8f8f8" : ""
                  }`}
                  >
                  <td className="ht-bg-f3f4fb nl-bold" colSpan={2}>
                    픽업
                  </td>
                  {/* <td className="ht-bg-f3f4fb nl-bold"></td> */}
                  <td>
                    {" "}
                    {rateData?.[0]?.rd_data1 ? rateData?.[0]?.rd_data1 : ""}
                  </td>
                  <td>
                    {rateData?.[0]?.rd_data2 ? format_number.format(rateData?.[0]?.rd_data2) : ""}
                  </td>
                  <td>
                    {rateData?.[0]?.rd_data3 ? rateData?.[0]?.rd_data3 : ""}
                  </td>
                  </tr> -->
         
               @if($count_service2_1 > 0)
               @if($rate_data_send_meta['rate_data2'][1]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_1}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     입고
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">정상입고</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][1]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][1]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][1]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][2]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][1]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_1}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     입고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">입고검품</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][2]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][2]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][2]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][3]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][1]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][2]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_1}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     입고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">반품입고</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][3]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][3]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][3]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][4]['rd_data3'] == 'ON')
               <tr>
                @if($rate_data_send_meta['rate_data2'][1]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][2]['rd_data3'] != 'ON'
                 && $rate_data_send_meta['rate_data2'][3]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_1}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     입고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">반품양품화</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][4]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][4]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][4]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
               @if($count_service2_2 > 0)
               @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">정상출고</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][5]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][5]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][5]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][6]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">합포장</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][6]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][6]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][6]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][7]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][6]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">사은품</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][7]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][7]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][7]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][8]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][6]['rd_data3'] != 'ON'
                   && $rate_data_send_meta['rate_data2'][7]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">반송출고</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][8]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][8]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][8]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][9]['rd_data3'] == 'ON')
               <tr>
                   @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][6]['rd_data3'] != 'ON'
                   && $rate_data_send_meta['rate_data2'][7]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][8]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">카튼출고</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][9]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][9]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][9]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][10]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][5]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][6]['rd_data3'] != 'ON'
                   && $rate_data_send_meta['rate_data2'][7]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][8]['rd_data3'] != 'ON'
                   && $rate_data_send_meta['rate_data2'][9]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_2}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     출고
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">B2B</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][10]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][10]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][10]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
               @if($count_service2_3 > 0)
               @if($rate_data_send_meta['rate_data2'][0]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_3}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     국내운송
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">
                     픽업
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][0]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][0]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][0]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][24]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][0]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_3}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     국내운송
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">배차(내륙운송)</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][24]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][24]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][24]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][25]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][0]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][24]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_3}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     국내운송
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">국내택배료</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][25]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][25]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][25]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
               @if($count_service2_4 > 0)
               @if($rate_data_send_meta['rate_data2'][26]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_4}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     해외운송
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">
                     해외운송료
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][26]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][26]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][26]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][27]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][26]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_4}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     해외운송
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">기타</td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][27]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][27]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][27]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
               @if($count_service2_5 > 0)
               @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">
                     기본료
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][14]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][14]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][14]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][15]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">PCS</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][15]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][15]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][15]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][16]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][15]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">CBM</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][16]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][16]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][16]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][17]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][15]['rd_data3'] != 'ON' 
                   && $rate_data_send_meta['rate_data2'][16]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">평</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][17]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][17]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][17]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][18]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][15]['rd_data3'] != 'ON' 
                   && $rate_data_send_meta['rate_data2'][16]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][17]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">PALET</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][18]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][18]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][18]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][19]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][14]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][15]['rd_data3'] != 'ON' 
                   && $rate_data_send_meta['rate_data2'][16]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data2'][17]['rd_data3'] != 'ON' 
                   && $rate_data_send_meta['rate_data2'][18]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_5}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     보관
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title"> 중량 (KG)</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][19]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][19]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][19]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
               @if($count_service2_6 > 0)
               @if($rate_data_send_meta['rate_data2'][22]['rd_data3'] == 'ON')
               <tr>
                  <td rowspan="{{$count_service2_6}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     부자재
                  </td>
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">
                     박스
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][22]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][22]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][22]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @if($rate_data_send_meta['rate_data2'][23]['rd_data3'] == 'ON')
               <tr>
                  @if($rate_data_send_meta['rate_data2'][22]['rd_data3'] != 'ON')
                  <td rowspan="{{$count_service2_6}}" style='font-family: "unbatang", Times, serif'  class="background_title">
                     부자재
                  </td>
                  @endif
                  <td style='font-family: "unbatang", Times, serif'  class="background_title">폴리백</td>
                  <td style='font-family: "unbatang", Times, serif'>
                     {{$rate_data_send_meta['rate_data2'][23]['rd_data1']}}
                  </td>
                  <td>
                     {{number_format($rate_data_send_meta['rate_data2'][23]['rd_data2'])}}
                  </td>
                  <td>
                     {{$rate_data_send_meta['rate_data2'][23]['rd_data3']}}
                  </td>
               </tr>
               @endif
               @endif
            </tbody>
         </table>
      </div>
      @endif



      @if(count($rate_data_send_meta['rate_data3']))
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr class="background_title">
                  <th colspan="2"  style='font-family: "unbatang", Times, serif' class="background_title">구분</th>
                  <th   style='font-family: "unbatang", Times, serif' class="background_title">단위</th>
                  <th   style='font-family: "unbatang", Times, serif' class="background_title">단가</th>
                  <th   style='font-family: "unbatang", Times, serif' class="background_title">ON/OFF</th>
               </tr>
            </thead>
            <tbody>
               @if($count_service3_1 > 0)  
                  @if($rate_data_send_meta['rate_data3'][0]['rd_data3'] == 'ON')
                  <tr>
                     <td rowspan="{{$count_service3_1}}" style='font-family: "unbatang", Times, serif' class="background_title">
                     원산지 표시
                     </td>
                     <td style='font-family: "unbatang", Times, serif' class="background_title">
                     각인
                     </td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][0]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][0]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][0]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][1]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][0]['rd_data3'] != 'ON')
                     <td rowspan="{{$count_service3_1}}" style='font-family: "unbatang", Times, serif' class="background_title">
                     원산지 표시
                     </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">불멸잉크</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][1]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][1]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][1]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][2]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][0]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data3'][1]['rd_data3'] != 'ON')
                     <td rowspan="{{$count_service3_1}}" style='font-family: "unbatang", Times, serif' class="background_title">
                     원산지 표시
                     </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">스티커</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][2]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][2]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][2]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][3]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][0]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data3'][1]['rd_data3'] != 'ON' 
                     && $rate_data_send_meta['rate_data3'][2]['rd_data3'] != 'ON')
                     <td rowspan="{{$count_service3_1}}" style='font-family: "unbatang", Times, serif' class="background_title">
                     원산지 표시
                     </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">박음질</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][3]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][3]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][3]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
               @endif
               @if($count_service3_2 > 0)   
               
                  @if($rate_data_send_meta['rate_data3'][4]['rd_data3'] == 'ON')
                  <tr>
                     <td rowspan="{{$count_service3_2}}" style='font-family: "unbatang", Times, serif' class="background_title">
                     TAG
                     </td>
                     <td style='font-family: "unbatang", Times, serif' class="background_title">
                     발행
                     </td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][4]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][4]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][4]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][5]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][4]['rd_data3'] != 'ON')
                        <td rowspan="{{$count_service3_2}}" style='font-family: "unbatang", Times, serif' class="background_title">
                        TAG
                        </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">부착</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][5]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][5]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][5]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][6]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][5]['rd_data3'] != 'ON' && $rate_data_send_meta['rate_data3'][4]['rd_data3'] != 'ON')
                        <td rowspan="{{$count_service3_2}}" style='font-family: "unbatang", Times, serif' class="background_title">
                        TAG
                        </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">교체</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][6]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][6]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][6]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
               @endif
               @if($count_service3_3 > 0)
                  @if($rate_data_send_meta['rate_data3'][7]['rd_data3'] == 'ON')
                  <tr>
                        <td rowspan="{{$count_service3_3}}" style='font-family: "unbatang", Times, serif' class="background_title">
                        라벨
                        </td>
                        <td style='font-family: "unbatang", Times, serif' class="background_title">
                        발행
                        </td>
                        <td style='font-family: "unbatang", Times, serif'>
                           {{$rate_data_send_meta['rate_data3'][7]['rd_data1']}}
                        </td>
                        <td>
                           {{number_format($rate_data_send_meta['rate_data3'][7]['rd_data2'])}}
                        </td>
                        <td>
                           {{$rate_data_send_meta['rate_data3'][7]['rd_data3']}}
                        </td>
                  </tr>
                  @endif
                  @if($rate_data_send_meta['rate_data3'][8]['rd_data3'] == 'ON')
                  <tr>
                     @if($rate_data_send_meta['rate_data3'][7]['rd_data3'] != 'ON')
                     <td rowspan="{{$count_service3_3}}" style='font-family: "unbatang", Times, serif' class="background_title">
                        라벨
                     </td>
                     @endif
                     <td style='font-family: "unbatang", Times, serif' class="background_title">부착</td>
                     <td style='font-family: "unbatang", Times, serif'>
                        {{$rate_data_send_meta['rate_data3'][8]['rd_data1']}}
                     </td>
                     <td>
                        {{number_format($rate_data_send_meta['rate_data3'][8]['rd_data2'])}}
                     </td>
                     <td>
                        {{$rate_data_send_meta['rate_data3'][8]['rd_data3']}}
                     </td>
                  </tr>
                  @endif
               @endif
               @if($rate_data_send_meta['rate_data3'][9]['rd_data3'] == 'ON')
               <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    화지(상품포장)
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][9]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][9]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][9]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][10]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    스티커작업
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][10]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][10]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][10]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][11]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    폴리백 교체
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][11]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][11]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][11]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][12]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    추가 동봉
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][12]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][12]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][12]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][13]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    박스교체
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][13]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][13]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][13]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][14]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    GIFT 포장
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][14]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][14]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][14]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][15]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    위험물포장
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][15]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][15]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][15]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][16]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    패키지
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][16]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][16]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][16]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][17]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    압축포장
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][17]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][17]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][17]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][18]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    분할
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][18]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][18]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][18]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][19]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    병합
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][19]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][19]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][19]['rd_data3']}}
                    </td>
                </tr>
                @endif
                @if($rate_data_send_meta['rate_data3'][20]['rd_data3'] == 'ON')
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif' class="background_title">
                    사전물품학인
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
                      {{$rate_data_send_meta['rate_data3'][20]['rd_data1']}}
                    </td>
                    <td>
                      {{number_format($rate_data_send_meta['rate_data3'][20]['rd_data2'])}}
                    </td>
                    <td>
                      {{$rate_data_send_meta['rate_data3'][20]['rd_data3']}}
                    </td>
                </tr>
                @endif
            </tbody>
         </table>
      </div>
      @endif
   </body>
</html>