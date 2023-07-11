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
         /* #custom_table td{
         } */
      </style>
   </head>
   <body>
      @if(count($rate_data_send_meta['rate_data1']))
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr>
                  <th rowspan="2" style='font-family: "unbatang", Times, serif'>구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif'>내역</th>
               </tr>
               <tr>
                  <th   style='font-family: "unbatang", Times, serif'>항목</th>
                  <th   style='font-family: "unbatang", Times, serif'>상세</th>
                  <th   style='font-family: "unbatang", Times, serif'>기본료</th>
                  <th   style='font-family: "unbatang", Times, serif'>단가/KG</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td rowspan="6"  style='font-family: "unbatang", Times, serif'>하역비용</td>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 0) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 14 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 15 && $key != 4 && $key !=2 && $key !=6 && $key !=10 && $key !=8 && ($key == 1 || $key == 3 || $key == 5 || $key == 7 || $key == 9) )
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 14 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endforeach
               <tr>
                  <td rowspan="4"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
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
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 15 && $key != 4 && $key !=2 && $key !=6 && $key !=10 && $key !=8 && ($key == 12 || $key == 13 || $key == 14) )
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
               @endforeach
            </tbody>
         </table>
      </div>
      @endif
      @if(count($rate_data_send_meta['rate_data1']))
      <div class="page-break"></div>
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr>
                  <th rowspan="2" style='font-family: "unbatang", Times, serif'>구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif'>내역</th>
               </tr>
               <tr>
                  <th   style='font-family: "unbatang", Times, serif'>항목</th>
                  <th   style='font-family: "unbatang", Times, serif'>상세</th>
                  <th   style='font-family: "unbatang", Times, serif'>기본료</th>
                  <th   style='font-family: "unbatang", Times, serif'>단가/KG</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td rowspan="6"  style='font-family: "unbatang", Times, serif'>하역비용</td>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 15) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 29 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 30 && $key != 19 && $key !=17 && $key !=21 && $key !=25 && $key !=23 && ($key == 16 || $key == 18 || $key == 20 || $key == 22 || $key == 24) )
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 29 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endforeach
               <tr>
                  <td rowspan="4"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
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
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 30 && $key != 19 && $key !=17 && $key !=21 && $key !=25 && $key !=23 && ($key == 27 || $key == 28 || $key == 29)  )
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
               @endforeach
            </tbody>
         </table>
      </div>
      @endif
      @if(count($rate_data_send_meta['rate_data1']))
      <div class="page-break"></div>
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr>
                  <th rowspan="2" style='font-family: "unbatang", Times, serif'>구분</th>
                  <th  colspan="4" style='font-family: "unbatang", Times, serif'>내역</th>
               </tr>
               <tr>
                  <th   style='font-family: "unbatang", Times, serif'>항목</th>
                  <th   style='font-family: "unbatang", Times, serif'>상세</th>
                  <th   style='font-family: "unbatang", Times, serif'>기본료</th>
                  <th   style='font-family: "unbatang", Times, serif'>단가/KG</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td rowspan="6"  style='font-family: "unbatang", Times, serif'>하역비용</td>
                  @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
                  @if($key == 30) 
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 44 ? number_format($row->rd_data2) : ''}}</td>
                  @endif
                  @endforeach
               </tr>
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 45 && $key != 34 && $key !=32 && $key !=36 && $key !=40 && $key !=38 && ($key == 31 || $key == 33 || $key == 35 || $key == 37 || $key == 39) )
               <tr>
                  <!-- <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate1}}</td> -->
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate2}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$row->rd_cate3}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{number_format($row->rd_data1)}}</td>
                  <td style='font-family: "unbatang", Times, serif'>{{$key != 44 ? number_format($row->rd_data2) : ''}}</td>
               </tr>
               @endif
               @endforeach
               <tr>
                  <td rowspan="4"  style='border-bottom: 0px;font-family: "unbatang", Times, serif'>센터 작업료</td>
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
               @foreach($rate_data_send_meta['rate_data1'] as $key => $row)
               @if($key < 45 && $key != 34 && $key !=35 && $key !=36 && $key !=40 && $key !=38 && ($key == 42 || $key == 43 || $key == 44)  )
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
               @endforeach
            </tbody>
         </table>
      </div>
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
               <tr>
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
               <tr>
                  <td rowspan="4" style='font-family: "unbatang", Times, serif'>
                     입고
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>정상입고</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>입고검품</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>반품입고</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>반품양품화</td>
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
               <tr>
                  <td rowspan="6" style='font-family: "unbatang", Times, serif'>
                     출고
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>정상출고</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>합포장</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>사은품</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>반송출고</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>카튼출고</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>B2B</td>
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
               <tr>
                  <td rowspan="3" style='font-family: "unbatang", Times, serif'>
                     국내운송
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>배차(내륙운송)</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>국내택배료</td>
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
               <tr>
                  <td rowspan="2" style='font-family: "unbatang", Times, serif'>
                     해외운송
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>기타</td>
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
               <tr>
                  <td rowspan="6" style='font-family: "unbatang", Times, serif'>
                     보관
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>PCS</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>CBM</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>평</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>PALET</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'> 중량 (KG)</td>
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
               <tr>
                  <td rowspan="2" style='font-family: "unbatang", Times, serif'>
                     부자재
                  </td>
                  <td style='font-family: "unbatang", Times, serif'>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>폴리백</td>
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
            </tbody>
         </table>
      </div>
      @endif



      @if(count($rate_data_send_meta['rate_data3']))
      <div style="border-bottom:1px solid #ddd">
         <table id="custom_table">
            <thead>
               <tr>
                  <th colspan="2"  style='font-family: "unbatang", Times, serif'>구분</th>
                  <th   style='font-family: "unbatang", Times, serif'>단위</th>
                  <th   style='font-family: "unbatang", Times, serif'>단가</th>
                  <th   style='font-family: "unbatang", Times, serif'>ON/OFF</th>
               </tr>
            </thead>
            <tbody>
              <tr>
                    <td rowspan="4" style='font-family: "unbatang", Times, serif'>
                    원산지 표시
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
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
                <tr>
                  <td style='font-family: "unbatang", Times, serif'>불멸잉크</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>스티커</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>박음질</td>
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

               <tr>
                    <td rowspan="3" style='font-family: "unbatang", Times, serif'>
                    TAG
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
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
                <tr>
                  <td style='font-family: "unbatang", Times, serif'>부착</td>
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
               <tr>
                  <td style='font-family: "unbatang", Times, serif'>교체</td>
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

               <tr>
                    <td rowspan="2" style='font-family: "unbatang", Times, serif'>
                    라벨
                    </td>
                    <td style='font-family: "unbatang", Times, serif'>
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
                <tr>
                  <td style='font-family: "unbatang", Times, serif'>부착</td>
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
               
               <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
                <tr>
                    <td colspan="2" style='font-family: "unbatang", Times, serif'>
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
            </tbody>
         </table>
      </div>
      @endif
   </body>
</html>