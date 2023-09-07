<!DOCTYPE html>
<html>

<head>
  <title>Send Mail</title>
  <style>
    .page-break {
      page-break-after: always;
    }

    #custom_table {
      width: 100%;
      border-collapse: collapse;
    }

    #custom_table td,
    #custom_table th {
      border: 1px solid #ddd;
      padding: 5px;
      text-align: center;
      font-family: "unbatang", Times, serif;
      font-size: 13px;
    }

    /* #custom_table td{
         } */
  </style>
</head>

<body>
  @if($service == '보세화물' && $mb_type == 'spasys')
  <div style="width: 100%;border:1px solid black;text-align: center;padding: 20px 0;">
    @if($tab_child == '창고화물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_sum1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
    @if($tab_child == '위험물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_vat1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
    @if($tab_child == '온도화물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_supply_price1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
  </div>
  <div style="width: 100%;margin-bottom:10px;margin-top:10px;text-align: right;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업자번호 : {{$co_license}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업장 주소 : {{$co_address}} {{$co_address_detail}}</p>
    @if($co_owner)
    @if($co_owner && !$co_email)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}}</p>
    @else
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}} ({{$co_email}})</p>
    @endif
    @endif
  </div>
  <div style="margin-bottom:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="5">화물 정보</th>
        </tr>
        <tr>
          <th>BL번호</th>

          <th>보관일수</th>

          <th>반출중량</th>

          <th>반출수량</th>

          <th>과세금액</th>

        </tr>
        @if($tab_child == '창고화물')
        <tr>
          <th>{{$rdg_sum1}}</th>
          <th>{{number_format($rdg_sum2)}}</th>
          <th>{{number_format($rdg_sum3)}}</th>
          <th>{{number_format($rdg_sum4)}}</th>
          <th>{{number_format($rdg_sum5)}}</th>
        </tr>
        @endif
        @if($tab_child == '위험물')
        <tr>
          <th>{{$rdg_vat1}}</th>
          <th>{{number_format($rdg_vat2)}}</th>
          <th>{{number_format($rdg_vat3)}}</th>
          <th>{{number_format($rdg_vat4)}}</th>
          <th>{{number_format($rdg_vat5)}}</th>
        </tr>
        @endif
        @if($tab_child == '온도화물')
        <tr>
          <th>{{$rdg_supply_price1}}</th>
          <th>{{number_format($rdg_supply_price2)}}</th>
          <th>{{number_format($rdg_supply_price3)}}</th>
          <th>{{number_format($rdg_supply_price4)}}</th>
          <th>{{number_format($rdg_supply_price5)}}</th>
        </tr>
        @endif
      </thead>
    </table>
  </div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @if(count($bonded1a) > 0 || count($bonded1b) > 0 || count($bonded1c) > 0)
        <tr>
          <td colspan="2">BLP센터비용</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
        @endif
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format(($total_1 ? $total_1 : 0))}}</td>
          <td>{{number_format(($total_2 ? $total_2 : 0))}}</td>
          <td>{{number_format(($total_3 ? $total_3 : 0))}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @if($tab_child == '창고화물')
  @if(count($bonded1a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1a as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif
  @if($tab_child == '온도화물')
  @if(count($bonded1b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1b as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif
  @if($tab_child == '위험물')
  @if(count($bonded1c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1c as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif

  </div>
  <div style="margin:10px 0px;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>1. 상세 업무 내역에 따라 예상비용은 변경될 수 있습니다.</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>2. 본 내역은 실제 비용이 아님을 명시합니다.</p>
  </div>
  <div style="text-align:right">
    @if($co_name_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name_send}}</p>
    @endif
    @if($co_address_send && $co_address_detail_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}} {{$co_address_detail_send}}</p>
    @elseif($co_address_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}}</p>
    @endif
    @if($co_tel_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_tel_send}}</p>
    @endif
    @if($co_email_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_email_send}}</p>
    @endif
    @if($date)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$date}}</p>
    @endif
  </div>
  @endif

  @if($service == '보세화물' && $mb_type != 'spasys')
  <div style="width: 100%;border:1px solid black;text-align: center;padding: 20px 0;">
    @if($tab_child == '창고화물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_sum1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
    @if($tab_child == '위험물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_vat1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
    @if($tab_child == '온도화물')
    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>보세화물({{$rdg_supply_price1}}) 예상비용_No{{$rmd_number}}</p>
    @endif
  </div>
  <div style="width: 100%;margin-top:10px;margin-bottom:10px;text-align: right;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업자번호 : {{$co_license}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업장 주소 : {{$co_address}} {{$co_address_detail}}</p>
    @if($co_owner)
    @if($co_owner && !$co_email)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}}</p>
    @else
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}} ({{$co_email}})</p>
    @endif
    @endif
  </div>
  <div style="margin-bottom:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="5">화물 정보</th>
        </tr>
        <tr>
          <th>BL번호</th>

          <th>보관일수</th>

          <th>반출중량</th>

          <th>반출수량</th>

          <th>과세금액</th>

        </tr>
        @if($tab_child == '창고화물')
        <tr>
          <th>{{$rdg_sum1}}</th>
          <th>{{number_format($rdg_sum2)}}</th>
          <th>{{number_format($rdg_sum3)}}</th>
          <th>{{number_format($rdg_sum4)}}</th>
          <th>{{number_format($rdg_sum5)}}</th>
        </tr>
        @endif
        @if($tab_child == '위험물')
        <tr>
          <th>{{$rdg_vat1}}</th>
          <th>{{number_format($rdg_vat2)}}</th>
          <th>{{number_format($rdg_vat3)}}</th>
          <th>{{number_format($rdg_vat4)}}</th>
          <th>{{number_format($rdg_vat5)}}</th>
        </tr>
        @endif
        @if($tab_child == '온도화물')
        <tr>
          <th>{{$rdg_supply_price1}}</th>
          <th>{{number_format($rdg_supply_price2)}}</th>
          <th>{{number_format($rdg_supply_price3)}}</th>
          <th>{{number_format($rdg_supply_price4)}}</th>
          <th>{{number_format($rdg_supply_price5)}}</th>
        </tr>
        @endif
      </thead>
    </table>
  </div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @if(count($bonded1a) > 0 || count($bonded1b) > 0 || count($bonded1c) > 0)
        <tr>
          <td colspan="2">BLP센터비용</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
        @endif
        @if(count($bonded2a) > 0 || count($bonded2b) > 0 || count($bonded2c) > 0)
        <tr>
          <td colspan="2">관세사비용</td>
          <td>{{number_format($sum2[0] ? $sum2[0] : 0)}}</td>
          <td>{{number_format($sum2[1] ? $sum2[1] : 0)}}</td>
          <td>{{number_format($sum2[2] ? $sum2[2] : 0)}}</td>
          <td>{{number_format($sum2[3] ? $sum2[3] : 0)}}</td>
          <td>{{number_format($sum2[4] ? $sum2[4] : 0)}}</td>
          <td>{{number_format($sum2[5] ? $sum2[5] : 0)}}</td>
          <td></td>
        </tr>
        @endif
        @if(count($bonded3a) > 0 || count($bonded3b) > 0 || count($bonded3c) > 0)
        <tr>
          <td colspan="2">포워더비용</td>
          <td>{{number_format($sum3[0] ? $sum3[0] : 0)}}</td>
          <td>{{number_format($sum3[1] ? $sum3[1] : 0)}}</td>
          <td>{{number_format($sum3[2] ? $sum3[2] : 0)}}</td>
          <td>{{number_format($sum3[3] ? $sum3[3] : 0)}}</td>
          <td>{{number_format($sum3[4] ? $sum3[4] : 0)}}</td>
          <td>{{number_format($sum3[5] ? $sum3[5] : 0)}}</td>
          <td></td>
        </tr>
        @endif
        @if(count($bonded4a) > 0 || count($bonded4b) > 0 || count($bonded4c) > 0)
        <tr>
          <td colspan="2">국내운송비</td>
          <td>{{number_format($sum4[0] ? $sum4[0] : 0)}}</td>
          <td>{{number_format($sum4[1] ? $sum4[1] : 0)}}</td>
          <td>{{number_format($sum4[2] ? $sum4[2] : 0)}}</td>
          <td>{{number_format($sum4[3] ? $sum4[3] : 0)}}</td>
          <td>{{number_format($sum4[4] ? $sum4[4] : 0)}}</td>
          <td>{{number_format($sum4[5] ? $sum4[5] : 0)}}</td>
          <td></td>
        </tr>
        @endif
        @if(count($bonded5a) > 0 || count($bonded5b) > 0 || count($bonded5c) > 0)
        <tr>
          <td colspan="2">요건비용</td>
          <td>{{number_format($sum5[0] ? $sum5[0] : 0)}}</td>
          <td>{{number_format($sum5[1] ? $sum5[1] : 0)}}</td>
          <td>{{number_format($sum5[2] ? $sum5[2] : 0)}}</td>
          <td>{{number_format($sum5[3] ? $sum5[3] : 0)}}</td>
          <td>{{number_format($sum5[4] ? $sum5[4] : 0)}}</td>
          <td>{{number_format($sum5[5] ? $sum5[5] : 0)}}</td>
          <td></td>
        </tr>
        @endif
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format(($total_1 ? $total_1 : 0) + (isset($sum5[0]) ? $sum5[0] : 0) + (isset($sum4[0]) ? $sum4[0] : 0) + (isset($sum3[0]) ? $sum3[0] : 0) + (isset($sum2[0]) ? $sum2[0] : 0))}}</td>
          <td>{{number_format(($total_2 ? $total_2 : 0) +(isset($sum5[1]) ? $sum5[1] : 0) + (isset($sum4[1]) ? $sum4[1] : 0) + (isset($sum3[1]) ? $sum3[1] : 0) + (isset($sum2[1]) ? $sum2[1] : 0))}}</td>
          <td>{{number_format(($total_3 ? $total_3 : 0) +(isset($sum5[2]) ? $sum5[2] : 0) + (isset($sum4[2]) ? $sum4[2] : 0) + (isset($sum3[2]) ? $sum3[2] : 0) + (isset($sum2[2]) ? $sum2[2] : 0))}}</td>
          <td>{{number_format((isset($sum5[3]) ? $sum5[3] : 0) + (isset($sum4[3]) ? $sum4[3] : 0) + (isset($sum3[3]) ? $sum3[3] : 0) + (isset($sum2[3]) ? $sum2[3] : 0))}}</td>
          <td>{{number_format((isset($sum5[4]) ? $sum5[4] : 0) + (isset($sum4[4]) ? $sum4[4] : 0) + (isset($sum3[4]) ? $sum3[4] : 0) + (isset($sum2[4]) ? $sum2[4] : 0))}}</td>
          <td>{{number_format((isset($sum5[5]) ? $sum5[5] : 0) + (isset($sum4[5]) ? $sum4[5] : 0) + (isset($sum3[5]) ? $sum3[5] : 0) + (isset($sum2[5]) ? $sum2[5] : 0))}}</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  </div>

  @if($tab_child == '창고화물')
  @if(count($bonded1a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1a as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded2a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 관세사비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded2a as $key => $row)
        @if($row->rd_cate1 == '세금' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1_2}}> 세금 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @if($row->rd_cate1 == '운임' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2_2}}> 운임 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif




        @if($row->rd_cate1 == '창고료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3_2}}> 창고료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif



        @if($row->rd_cate1 == '수수료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count4_2}}> 수수료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>1
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @endforeach

        @for ($i = 0; $i < count($arr2); $i++) @foreach($bonded2a as $key=> $row)
          @if($row->rd_cate1 == $arr2[$i] && $row->rd_cate2 != 'bonded2')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr2[$i]}}> {{$arr2[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum2[0] ? $sum2[0] : 0)}}</td>
            <td>{{number_format($sum2[1] ? $sum2[1] : 0)}}</td>
            <td>{{number_format($sum2[2] ? $sum2[2] : 0)}}</td>
            <td>{{number_format($sum2[3] ? $sum2[3] : 0)}}</td>
            <td>{{number_format($sum2[4] ? $sum2[4] : 0)}}</td>
            <td>{{number_format($sum2[5] ? $sum2[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded3a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 포워더비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr3); $i++) @foreach($bonded3a as $key=> $row)
          @if($row->rd_cate1 == $arr3[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr3[$i]}}> {{$arr3[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum3[0] ? $sum3[0] : 0)}}</td>
            <td>{{number_format($sum3[1] ? $sum3[1] : 0)}}</td>
            <td>{{number_format($sum3[2] ? $sum3[2] : 0)}}</td>
            <td>{{number_format($sum3[3] ? $sum3[3] : 0)}}</td>
            <td>{{number_format($sum3[4] ? $sum3[4] : 0)}}</td>
            <td>{{number_format($sum3[5] ? $sum3[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded4a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 국내운송비</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr4); $i++) @foreach($bonded4a as $key=> $row)
          @if($row->rd_cate1 == $arr4[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr4[$i]}}> {{$arr4[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum4[0] ? $sum4[0] : 0)}}</td>
            <td>{{number_format($sum4[1] ? $sum4[1] : 0)}}</td>
            <td>{{number_format($sum4[2] ? $sum4[2] : 0)}}</td>
            <td>{{number_format($sum4[3] ? $sum4[3] : 0)}}</td>
            <td>{{number_format($sum4[4] ? $sum4[4] : 0)}}</td>
            <td>{{number_format($sum4[5] ? $sum4[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded5a) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 요건비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr5); $i++) @foreach($bonded5a as $key=> $row)
          @if($row->rd_cate1 == $arr5[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr5[$i]}}> {{$arr5[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum5[0] ? $sum5[0] : 0)}}</td>
            <td>{{number_format($sum5[1] ? $sum5[1] : 0)}}</td>
            <td>{{number_format($sum5[2] ? $sum5[2] : 0)}}</td>
            <td>{{number_format($sum5[3] ? $sum5[3] : 0)}}</td>
            <td>{{number_format($sum5[4] ? $sum5[4] : 0)}}</td>
            <td>{{number_format($sum5[5] ? $sum5[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif
  @if($tab_child == '온도화물')
  @if(count($bonded1b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1b as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded2b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 관세사비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded2b as $key => $row)
        @if($row->rd_cate1 == '세금' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1_2}}> 세금 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @if($row->rd_cate1 == '운임' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2_2}}> 운임 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif




        @if($row->rd_cate1 == '창고료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3_2}}> 창고료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif



        @if($row->rd_cate1 == '수수료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count4_2}}> 수수료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>1
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @endforeach

        @for ($i = 0; $i < count($arr2); $i++) @foreach($bonded2b as $key=> $row)
          @if($row->rd_cate1 == $arr2[$i] && $row->rd_cate2 != 'bonded2')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr2[$i]}}> {{$arr2[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum2[0] ? $sum2[0] : 0)}}</td>
            <td>{{number_format($sum2[1] ? $sum2[1] : 0)}}</td>
            <td>{{number_format($sum2[2] ? $sum2[2] : 0)}}</td>
            <td>{{number_format($sum2[3] ? $sum2[3] : 0)}}</td>
            <td>{{number_format($sum2[4] ? $sum2[4] : 0)}}</td>
            <td>{{number_format($sum2[5] ? $sum2[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded3b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 포워더비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr3); $i++) @foreach($bonded3b as $key=> $row)
          @if($row->rd_cate1 == $arr3[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr3[$i]}}> {{$arr3[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum3[0] ? $sum3[0] : 0)}}</td>
            <td>{{number_format($sum3[1] ? $sum3[1] : 0)}}</td>
            <td>{{number_format($sum3[2] ? $sum3[2] : 0)}}</td>
            <td>{{number_format($sum3[3] ? $sum3[3] : 0)}}</td>
            <td>{{number_format($sum3[4] ? $sum3[4] : 0)}}</td>
            <td>{{number_format($sum3[5] ? $sum3[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded4b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 국내운송비</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr4); $i++) @foreach($bonded4b as $key=> $row)
          @if($row->rd_cate1 == $arr4[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr4[$i]}}> {{$arr4[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum4[0] ? $sum4[0] : 0)}}</td>
            <td>{{number_format($sum4[1] ? $sum4[1] : 0)}}</td>
            <td>{{number_format($sum4[2] ? $sum4[2] : 0)}}</td>
            <td>{{number_format($sum4[3] ? $sum4[3] : 0)}}</td>
            <td>{{number_format($sum4[4] ? $sum4[4] : 0)}}</td>
            <td>{{number_format($sum4[5] ? $sum4[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded5b) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 요건비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr5); $i++) @foreach($bonded5b as $key=> $row)
          @if($row->rd_cate1 == $arr5[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr5[$i]}}> {{$arr5[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr>
            <td colspan="2">합계</td>
            <td>{{number_format($sum5[0] ? $sum5[0] : 0)}}</td>
            <td>{{number_format($sum5[1] ? $sum5[1] : 0)}}</td>
            <td>{{number_format($sum5[2] ? $sum5[2] : 0)}}</td>
            <td>{{number_format($sum5[3] ? $sum5[3] : 0)}}</td>
            <td>{{number_format($sum5[4] ? $sum5[4] : 0)}}</td>
            <td>{{number_format($sum5[5] ? $sum5[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif
  @if($tab_child == '위험물')
  @if(count($bonded1c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> BLP센터비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded1c as $key => $row)
        @if($row->rd_cate1 == '하역비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1}}> 하역비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '센터 작업료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2}}> 센터 작업료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          @if($row->rd_cate2 == '할인율')
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}} %</td>
          @else
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          @endif
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @if($row->rd_cate1 == '기타 비용' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3}}> 기타 비용 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td></td>
          <td></td>
          <td></td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif
        @endforeach
        <tr>
          <td colspan="2">합계</td>
          <td>{{number_format($total_1)}}</td>
          <td>{{number_format($total_2)}}</td>
          <td>{{number_format($total_3)}}</td>
          <td>0</td>
          <td>0</td>
          <td>0</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded2c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 관세사비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @foreach($bonded2c as $key => $row)
        @if($row->rd_cate1 == '세금' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count1_2}}> 세금 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @if($row->rd_cate1 == '운임' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count2_2}}> 운임 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif




        @if($row->rd_cate1 == '창고료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count3_2}}> 창고료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif



        @if($row->rd_cate1 == '수수료' && $row->rd_cate1 != $row->rd_cate2)
        @if($row->rd_cate2 == '소계')
        <tr>
          <td rowspan={{$count4_2}}> 수수료 </td>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>1
          <td>{{$row->rd_data8}}</td>
        </tr>
        @else
        <tr>
          <td>{{$row->rd_cate2}}</td>
          <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
          <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
          <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
          <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
          <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
          <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
          <td>{{$row->rd_data8}}</td>
        </tr>
        @endif
        @endif


        @endforeach

        @for ($i = 0; $i < count($arr2); $i++) @foreach($bonded2c as $key=> $row)
          @if($row->rd_cate1 == $arr2[$i] && $row->rd_cate2 != 'bonded2')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr2[$i]}}> {{$arr2[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr style='border-bottom: 0px'>
            <td colspan="2">합계</td>
            <td>{{number_format($sum2[0] ? $sum2[0] : 0)}}</td>
            <td>{{number_format($sum2[1] ? $sum2[1] : 0)}}</td>
            <td>{{number_format($sum2[2] ? $sum2[2] : 0)}}</td>
            <td>{{number_format($sum2[3] ? $sum2[3] : 0)}}</td>
            <td>{{number_format($sum2[4] ? $sum2[4] : 0)}}</td>
            <td>{{number_format($sum2[5] ? $sum2[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded3c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 포워더비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr3); $i++) @foreach($bonded3c as $key=> $row)
          @if($row->rd_cate1 == $arr3[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr3[$i]}}> {{$arr3[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr style='border-bottom: 0px'>
            <td colspan="2">합계</td>
            <td>{{number_format($sum3[0] ? $sum3[0] : 0)}}</td>
            <td>{{number_format($sum3[1] ? $sum3[1] : 0)}}</td>
            <td>{{number_format($sum3[2] ? $sum3[2] : 0)}}</td>
            <td>{{number_format($sum3[3] ? $sum3[3] : 0)}}</td>
            <td>{{number_format($sum3[4] ? $sum3[4] : 0)}}</td>
            <td>{{number_format($sum3[5] ? $sum3[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded4c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 국내운송비</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr4); $i++) @foreach($bonded4c as $key=> $row)
          @if($row->rd_cate1 == $arr4[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr4[$i]}}> {{$arr4[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr style='border-bottom: 0px'>
            <td colspan="2">합계</td>
            <td>{{number_format($sum4[0] ? $sum4[0] : 0)}}</td>
            <td>{{number_format($sum4[1] ? $sum4[1] : 0)}}</td>
            <td>{{number_format($sum4[2] ? $sum4[2] : 0)}}</td>
            <td>{{number_format($sum4[3] ? $sum4[3] : 0)}}</td>
            <td>{{number_format($sum4[4] ? $sum4[4] : 0)}}</td>
            <td>{{number_format($sum4[5] ? $sum4[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if(count($bonded5c) > 0)
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9"> 요건비용</th>
        </tr>
        <tr>
          <th rowspan="2" colspan="2">항목</th>
          <th colspan="3">세금계산서 발행</th>
          <th colspan="3">세금계산서 미발행</th>
          <th rowspan="2">비고</th>
        </tr>
        <tr>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
        </tr>
      </thead>
      <tbody>
        @for ($i = 0; $i < count($arr5); $i++) @foreach($bonded5c as $key=> $row)
          @if($row->rd_cate1 == $arr5[$i] && $row->rd_cate2 != 'bonded345')
          @if($row->rd_cate2 == '소계')
          <tr>
            <td rowspan={{$count_arr5[$i]}}> {{$arr5[$i]}} </td>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @else
          <tr>
            <td>{{$row->rd_cate2}}</td>
            <td>{{number_format($row->rd_data1 ? $row->rd_data1 : 0)}}</td>
            <td>{{number_format($row->rd_data2 ? $row->rd_data2 : 0)}}</td>
            <td>{{number_format($row->rd_data4 ? $row->rd_data4 : 0)}}</td>
            <td>{{number_format($row->rd_data5 ? $row->rd_data5 : 0)}}</td>
            <td>{{number_format($row->rd_data6 ? $row->rd_data6 : 0)}}</td>
            <td>{{number_format($row->rd_data7 ? $row->rd_data7 : 0)}}</td>
            <td>{{$row->rd_data8}}</td>
          </tr>
          @endif
          @endif
          @endforeach
          @endfor
          <tr style='border-bottom: 0px'>
            <td colspan="2">합계</td>
            <td>{{number_format($sum5[0] ? $sum5[0] : 0)}}</td>
            <td>{{number_format($sum5[1] ? $sum5[1] : 0)}}</td>
            <td>{{number_format($sum5[2] ? $sum5[2] : 0)}}</td>
            <td>{{number_format($sum5[3] ? $sum5[3] : 0)}}</td>
            <td>{{number_format($sum5[4] ? $sum5[4] : 0)}}</td>
            <td>{{number_format($sum5[5] ? $sum5[5] : 0)}}</td>
            <td></td>
          </tr>
      </tbody>
    </table>
  </div>
  @endif
  @endif
  <div style="margin:10px 0px;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>1. 상세 업무 내역에 따라 예상비용은 변경될 수 있습니다.</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>2. 본 내역은 실제 비용이 아님을 명시합니다.</p>
  </div>
  <div style="text-align:right">
    @if($co_name_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name_send}}</p>
    @endif
    @if($co_address_send && $co_address_detail_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}} {{$co_address_detail_send}}</p>
    @elseif($co_address_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}}</p>
    @endif
    @if($co_tel_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_tel_send}}</p>
    @endif
    @if($co_email_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_email_send}}</p>
    @endif
    @if($date)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$date}}</p>
    @endif
  </div>
  @endif

  @if($service == '수입풀필먼트')
  <div style="width: 100%;border:1px solid black;text-align: center;padding: 20px 0;">

    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>수입풀필먼트 예상비용_No{{$rmd_number}}</p>

  </div>
  <div style="width: 100%;margin-bottom:10px;margin-top:10px;text-align: right;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업자번호 : {{$co_license}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업장 주소 : {{$co_address}} {{$co_address_detail}}</p>
    @if($co_owner)
    @if($co_owner && !$co_email)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}}</p>
    @else
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}} ({{$co_email}})</p>
    @endif
    @endif
  </div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="5">합계</td>
        </tr>
        <tr>
          <th>항목</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>비고</th>
        <tr>
      </thead>
      <tbody>
        @if($rate_data_general['rdg_etc1'] || $rate_data_general['rdg_sum1'])
        <tr>
          <td>입/출고료</td>
          <td>{{number_format($rate_data_general['rdg_supply_price1'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat1'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum1'])}}</td>
          <td>{{$rate_data_general['rdg_etc1']}}</td>
        </tr>
        @endif
        @if($rate_data_general['rdg_etc2'] || $rate_data_general['rdg_sum2'])
        <tr>
          <td>국내운송료</td>
          <td>{{number_format($rate_data_general['rdg_supply_price2'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat2'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum2'])}}</td>
          <td>{{$rate_data_general['rdg_etc2']}}</td>
        </tr>
        @endif
        @if($rate_data_general['rdg_etc3'] || $rate_data_general['rdg_sum3'])
        <tr>
          <td>해외운송료</td>
          <td>{{number_format($rate_data_general['rdg_supply_price3'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat3'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum3'])}}</td>
          <td>{{$rate_data_general['rdg_etc3']}}</td>
        </tr>
        @endif
        @if($rate_data_general['rdg_etc4'] || $rate_data_general['rdg_sum4'])
        <tr>
          <td>보관</td>
          <td>{{number_format($rate_data_general['rdg_supply_price4'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat4'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum4'])}}</td>
          <td>{{$rate_data_general['rdg_etc4']}}</td>
        </tr>
        @endif
        @if($rate_data_general['rdg_etc5'] || $rate_data_general['rdg_sum5'])
        <tr>
          <td>부자재</td>
          <td>{{number_format($rate_data_general['rdg_supply_price5'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat5'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum5'])}}</td>
          <td>{{$rate_data_general['rdg_etc5']}}</td>
        </tr>
        @endif
        <tr>
          <td>합계</td>
          <td>{{number_format($rate_data_general['rdg_supply_price6'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat6'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum6'])}}</td>
          <td>{{$rate_data_general['rdg_etc6']}}</td>
        </tr>
      </tbody>
    </table>
  </div>

  @if($rate_data_general['rdg_sum1'] && $rate_data_general['rdg_sum1'] > 0 )

  <div style="margin-top:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="9">입/출고료</td>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="5" style='font-family: "unbatang", Times, serif'>
            입고
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            정상입고
          </td>
          <td>
            {{$rate_data[0]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[0]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            입고검품
          </td>
          <td>
            {{$rate_data[1]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[1]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            반품입고
          </td>
          <td>
            {{$rate_data[2]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[2]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            반품양품화
          </td>
          <td>
            {{$rate_data[3]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[3]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            소계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data2'] + $rate_data[0]['rd_data2'] + $rate_data[1]['rd_data2'] +$rate_data[2]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data4'] + $rate_data[0]['rd_data4'] + $rate_data[1]['rd_data4'] +$rate_data[2]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data5'] + $rate_data[0]['rd_data5'] + $rate_data[1]['rd_data5'] +$rate_data[2]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data6'] + $rate_data[0]['rd_data6'] + $rate_data[1]['rd_data6'] +$rate_data[2]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data7'] + $rate_data[0]['rd_data7'] + $rate_data[1]['rd_data7'] +$rate_data[2]['rd_data7'])}}
          </td>
          <td>
          </td>
        </tr>
        <tr>
          <td rowspan="8" style='font-family: "unbatang", Times, serif'>
            출고
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            정상입고
          </td>
          <td>
            {{$rate_data[4]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[4]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            합포장
          </td>
          <td>
            {{$rate_data[5]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[5]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            합포장
          </td>
          <td>
            {{$rate_data[6]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[6]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            사은품
          </td>
          <td>
            {{$rate_data[7]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[7]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            반송출고
          </td>
          <td>
            {{$rate_data[8]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[8]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            카톤출고
          </td>
          <td>
            {{$rate_data[9]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[9]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            B2B
          </td>
          <td>
            {{$rate_data[10]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[10]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            소계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data2'] + $rate_data[5]['rd_data2'] + $rate_data[6]['rd_data2'] +$rate_data[7]['rd_data2']+$rate_data[8]['rd_data2']+$rate_data[9]['rd_data2']
                            +$rate_data[10]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data4'] + $rate_data[5]['rd_data4'] + $rate_data[6]['rd_data4'] +$rate_data[7]['rd_data4']+$rate_data[8]['rd_data4']+$rate_data[9]['rd_data4']
                            +$rate_data[10]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data5'] + $rate_data[5]['rd_data5'] + $rate_data[6]['rd_data5'] +$rate_data[7]['rd_data5']+$rate_data[8]['rd_data5']+$rate_data[9]['rd_data5']
                            +$rate_data[10]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data6'] + $rate_data[5]['rd_data6'] + $rate_data[6]['rd_data6'] +$rate_data[7]['rd_data6']+$rate_data[8]['rd_data6']+$rate_data[9]['rd_data6']
                            +$rate_data[10]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data7'] + $rate_data[5]['rd_data7'] + $rate_data[6]['rd_data7'] +$rate_data[7]['rd_data7']+$rate_data[8]['rd_data7']+$rate_data[9]['rd_data7']
                            +$rate_data[10]['rd_data7'])}}
          </td>
          <td>
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data2'] + $rate_data[0]['rd_data2'] + $rate_data[1]['rd_data2'] +$rate_data[2]['rd_data2'] + $rate_data[4]['rd_data2'] + $rate_data[5]['rd_data2'] + $rate_data[6]['rd_data2'] +$rate_data[7]['rd_data2']+$rate_data[8]['rd_data2']+$rate_data[9]['rd_data2']
                            +$rate_data[10]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data4'] + $rate_data[0]['rd_data4'] + $rate_data[1]['rd_data4'] +$rate_data[2]['rd_data4'] + $rate_data[4]['rd_data4'] + $rate_data[5]['rd_data4'] + $rate_data[6]['rd_data4'] +$rate_data[7]['rd_data4']+$rate_data[8]['rd_data4']+$rate_data[9]['rd_data4']
                            +$rate_data[10]['rd_data4'] )}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data5'] + $rate_data[0]['rd_data5'] + $rate_data[1]['rd_data5'] +$rate_data[2]['rd_data5'] + $rate_data[4]['rd_data5'] + $rate_data[5]['rd_data5'] + $rate_data[6]['rd_data5'] +$rate_data[7]['rd_data5']+$rate_data[8]['rd_data5']+$rate_data[9]['rd_data5']
                            +$rate_data[10]['rd_data5']) }}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data6'] + $rate_data[0]['rd_data6'] + $rate_data[1]['rd_data6'] +$rate_data[2]['rd_data6'] + $rate_data[4]['rd_data6'] + $rate_data[5]['rd_data6'] + $rate_data[6]['rd_data6'] +$rate_data[7]['rd_data6']+$rate_data[8]['rd_data6']+$rate_data[9]['rd_data6']
                            +$rate_data[10]['rd_data6']) }}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data7'] + $rate_data[0]['rd_data7'] + $rate_data[1]['rd_data7'] +$rate_data[2]['rd_data7'] + $rate_data[4]['rd_data7'] + $rate_data[5]['rd_data7'] + $rate_data[6]['rd_data7'] +$rate_data[7]['rd_data7']+$rate_data[8]['rd_data7']+$rate_data[9]['rd_data7']
                            + $rate_data[10]['rd_data7'])}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if($rate_data_general['rdg_sum2'] && $rate_data_general['rdg_sum2'] > 0 )
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="9">국내운송료</td>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="3" style='font-family: "unbatang", Times, serif'>
            입고
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            픽업료
          </td>
          <td>
            {{$rate_data[10]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[10]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            배차(내륙운송)
          </td>
          <td>
            {{$rate_data[11]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[11]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            국내 택배료
          </td>
          <td>
            {{$rate_data[12]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[12]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data2'] + $rate_data[11]['rd_data2'] + $rate_data[12]['rd_data2'] )}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data4'] + $rate_data[12]['rd_data4'] + $rate_data[10]['rd_data4'] ) }}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data5'] + $rate_data[12]['rd_data5'] + $rate_data[10]['rd_data5'] ) }}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data6'] + $rate_data[11]['rd_data6'] + $rate_data[10]['rd_data6'] ) }}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data7'] + $rate_data[11]['rd_data7'] + $rate_data[10]['rd_data7'])}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if($rate_data_general['rdg_sum3'] && $rate_data_general['rdg_sum3'] > 0 )

  <div style="margin-top:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="9">해외운송료</td>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="2" style='font-family: "unbatang", Times, serif'>
            운송
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            해외운송료
          </td>
          <td>
            {{$rate_data[13]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[13]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
            {{$rate_data[14]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[14]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data2'] + $rate_data[13]['rd_data2']  )}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data4'] + $rate_data[14]['rd_data4']  ) }}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data5'] + $rate_data[14]['rd_data5']  ) }}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data6'] + $rate_data[13]['rd_data6']  ) }}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data7'] + $rate_data[13]['rd_data7'] )}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if($rate_data_general['rdg_sum4'] && $rate_data_general['rdg_sum4'] > 0 )

  <div style="margin-top:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="9">보관</td>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="6" style='font-family: "unbatang", Times, serif'>
            보관
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            기본료
          </td>
          <td>
            {{$rate_data[15]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[15]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            PCS
          </td>
          <td>
            {{$rate_data[16]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[16]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            CBM
          </td>
          <td>
            {{$rate_data[17]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[17]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            평
          </td>
          <td>
            {{$rate_data[18]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[18]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            PALET
          </td>
          <td>
            {{$rate_data[19]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[19]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            중량 (KG)
          </td>
          <td>
            {{$rate_data[20]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[20]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data2'] + $rate_data[16]['rd_data2'] + $rate_data[17]['rd_data2'] + $rate_data[18]['rd_data2'] 
                            + $rate_data[19]['rd_data2'] + $rate_data[20]['rd_data2']  )}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data4'] + $rate_data[16]['rd_data4'] + $rate_data[17]['rd_data4'] + $rate_data[18]['rd_data4'] 
                            + $rate_data[19]['rd_data4'] + $rate_data[20]['rd_data4']  )}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data5'] + $rate_data[16]['rd_data5'] + $rate_data[17]['rd_data5'] + $rate_data[18]['rd_data5'] 
                            + $rate_data[19]['rd_data5'] + $rate_data[20]['rd_data5']  )}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data6'] + $rate_data[16]['rd_data6'] + $rate_data[17]['rd_data6'] + $rate_data[18]['rd_data6'] 
                            + $rate_data[19]['rd_data6'] + $rate_data[20]['rd_data6']  )}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data7'] + $rate_data[16]['rd_data7'] + $rate_data[17]['rd_data7'] + $rate_data[18]['rd_data7'] 
                            + $rate_data[19]['rd_data7'] + $rate_data[20]['rd_data7']  )}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if($rate_data_general['rdg_sum5'] && $rate_data_general['rdg_sum5'] > 0 )

  <div style="margin-top:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <td colspan="9">부자재</td>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="2" style='font-family: "unbatang", Times, serif'>
            부자재
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            박스
          </td>
          <td>
            {{$rate_data[23]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[23]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            폴리백
          </td>
          <td>
            {{$rate_data[24]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[24]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data2'] + $rate_data[24]['rd_data2']  )}}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data4'] + $rate_data[23]['rd_data4']  ) }}
          </td>
          <td>
            {{number_format($rate_data[24]['rd_data5'] + $rate_data[23]['rd_data5']  ) }}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data6'] + $rate_data[24]['rd_data6']  ) }}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data7'] + $rate_data[24]['rd_data7'] )}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  <div style="margin:10px 0px;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>1. 상세 업무 내역에 따라 예상비용은 변경될 수 있습니다.</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>2. 본 내역은 실제 비용이 아님을 명시합니다.</p>
  </div>
  <div style="text-align:right">
    @if($co_name_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name_send}}</p>
    @endif
    @if($co_address_send && $co_address_detail_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}} {{$co_address_detail_send}}</p>
    @elseif($co_address_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}}</p>
    @endif
    @if($co_tel_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_tel_send}}</p>
    @endif
    @if($co_email_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_email_send}}</p>
    @endif
    @if($date)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$date}}</p>
    @endif
  </div>
  @endif
  @if($service == '유통가공')
  <div style="width: 100%;border:1px solid black;text-align: center;padding: 20px 0;">

    <p style='font-size:16px;padding:0px;font-family: "unbatang", Times, serif;font-weight:bold'>유통가공 예상비용_No{{$rmd_number}}</p>

  </div>
  <div style="width: 100%;margin-bottom:10px;margin-top:10px;text-align: right;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업자번호 : {{$co_license}}</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>사업장 주소 : {{$co_address}} {{$co_address_detail}}</p>
    @if($co_owner)
    @if($co_owner && !$co_email)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}}</p>
    @else
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>대표자명 : {{$co_owner}} ({{$co_email}})</p>
    @endif
    @endif
  </div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="5">합계</th>
        </tr>
        <tr>
          <th>항목</th>
          <th>공급가</th>
          <th>부가세</th>
          <th>합계</th>
          <th>비고</th>
        <tr>
      </thead>
      <tbody>
        @if($rate_data_general['rdg_etc1'] || $rate_data_general['rdg_sum1'])
        <tr>
          <td>작업료</td>
          <td>{{number_format($rate_data_general['rdg_supply_price1'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat1'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum1'])}}</td>
          <td>{{$rate_data_general['rdg_etc1']}}</td>
        </tr>
        @endif
        @if($rate_data_general['rdg_etc2'] || $rate_data_general['rdg_sum2'])
        <tr>
          <td>보관료</td>
          <td>{{number_format($rate_data_general['rdg_supply_price2'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat2'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum2'])}}</td>
          <td>{{$rate_data_general['rdg_etc2']}}</td>
        </tr>
        @endif
        <tr>
          <td>합계</td>
          <td>{{number_format($rate_data_general['rdg_supply_price6'])}}</td>
          <td>{{number_format($rate_data_general['rdg_vat6'])}}</td>
          <td>{{number_format($rate_data_general['rdg_sum6'])}}</td>
          <td>{{$rate_data_general['rdg_etc6']}}</td>
        </tr>
      </tbody>
    </table>
  </div>
  @if($rate_data_general['rdg_sum1'] && $rate_data_general['rdg_sum1'] > 0 )

  <div style="margin-top:10px">
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9">작업료</th>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
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
          <td>
            {{$rate_data[0]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[0]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[0]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            불멸잉크
          </td>
          <td>
            {{$rate_data[1]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[1]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[1]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            스티커
          </td>
          <td>
            {{$rate_data[2]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[2]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[2]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            박음질
          </td>
          <td>
            {{$rate_data[3]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[3]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[3]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td rowspan="3" style='font-family: "unbatang", Times, serif'>
            TAG
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            발행
          </td>
          <td>
            {{$rate_data[4]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[4]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[4]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            부착
          </td>
          <td>
            {{$rate_data[5]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[5]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[5]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            교체
          </td>
          <td>
            {{$rate_data[6]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[6]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[6]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td rowspan="2" style='font-family: "unbatang", Times, serif'>
            라벨
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            발행
          </td>
          <td>
            {{$rate_data[7]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[7]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[7]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            부착
          </td>
          <td>
            {{$rate_data[8]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[8]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[8]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            화지(상품포장)
          </td>
          <td>
            {{$rate_data[9]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[9]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[9]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            스트커작업
          </td>
          <td>
            {{$rate_data[10]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[10]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[10]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            폴리백 교체
          </td>
          <td>
            {{$rate_data[11]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[11]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[11]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            추가 동봉
          </td>
          <td>
            {{$rate_data[12]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[12]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[12]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            박스교체
          </td>
          <td>
            {{$rate_data[13]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[13]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[13]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            GIFT 포장
          </td>
          <td>
            {{$rate_data[14]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[14]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[14]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            위험물포장
          </td>
          <td>
            {{$rate_data[15]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[15]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[15]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            패키지
          </td>
          <td>
            {{$rate_data[16]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[16]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[16]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            압축포장
          </td>
          <td>
            {{$rate_data[17]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[17]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[17]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            분할
          </td>
          <td>
            {{$rate_data[18]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[18]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[18]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            병합
          </td>
          <td>
            {{$rate_data[19]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[19]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[19]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            사전물품확인
          </td>
          <td>
            {{$rate_data[20]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[20]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data4'] + $rate_data[19]['rd_data4'] +  $rate_data[18]['rd_data4']
                        +  $rate_data[17]['rd_data4'] +  $rate_data[16]['rd_data4'] +  $rate_data[15]['rd_data4']
                        +  $rate_data[14]['rd_data4'] +  $rate_data[13]['rd_data4'] +  $rate_data[12]['rd_data4']
                        +  $rate_data[11]['rd_data4'] +  $rate_data[10]['rd_data4'] +  $rate_data[9]['rd_data4']
                        +  $rate_data[8]['rd_data4'] +  $rate_data[7]['rd_data4'] +  $rate_data[6]['rd_data4']
                        +  $rate_data[5]['rd_data4'] +  $rate_data[4]['rd_data4'] +  $rate_data[3]['rd_data4']
                        +  $rate_data[2]['rd_data4'] +  $rate_data[1]['rd_data4'] +  $rate_data[0]['rd_data4']  )}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data5'] + $rate_data[19]['rd_data5'] +  $rate_data[18]['rd_data5']
                        +  $rate_data[17]['rd_data5'] +  $rate_data[16]['rd_data5'] +  $rate_data[15]['rd_data5']
                        +  $rate_data[14]['rd_data5'] +  $rate_data[13]['rd_data5'] +  $rate_data[12]['rd_data5']
                        +  $rate_data[11]['rd_data5'] +  $rate_data[10]['rd_data5'] +  $rate_data[9]['rd_data5']
                        +  $rate_data[8]['rd_data5'] +  $rate_data[7]['rd_data5'] +  $rate_data[6]['rd_data5']
                        +  $rate_data[5]['rd_data5'] +  $rate_data[4]['rd_data5'] +  $rate_data[3]['rd_data5']
                        +  $rate_data[2]['rd_data5'] +  $rate_data[1]['rd_data5'] +  $rate_data[0]['rd_data5']  )}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data6'] + $rate_data[19]['rd_data6'] +  $rate_data[18]['rd_data6']
                        +  $rate_data[17]['rd_data6'] +  $rate_data[16]['rd_data6'] +  $rate_data[15]['rd_data6']
                        +  $rate_data[14]['rd_data6'] +  $rate_data[13]['rd_data6'] +  $rate_data[12]['rd_data6']
                        +  $rate_data[11]['rd_data6'] +  $rate_data[10]['rd_data6'] +  $rate_data[9]['rd_data6']
                        +  $rate_data[8]['rd_data6'] +  $rate_data[7]['rd_data6'] +  $rate_data[6]['rd_data6']
                        +  $rate_data[5]['rd_data6'] +  $rate_data[4]['rd_data6'] +  $rate_data[3]['rd_data6']
                        +  $rate_data[2]['rd_data6'] +  $rate_data[1]['rd_data6'] +  $rate_data[0]['rd_data6']  )}}
          </td>
          <td>
            {{number_format($rate_data[20]['rd_data7'] + $rate_data[19]['rd_data7'] +  $rate_data[18]['rd_data7']
                        +  $rate_data[17]['rd_data7'] +  $rate_data[16]['rd_data7'] +  $rate_data[15]['rd_data7']
                        +  $rate_data[14]['rd_data7'] +  $rate_data[13]['rd_data7'] +  $rate_data[12]['rd_data7']
                        +  $rate_data[11]['rd_data7'] +  $rate_data[10]['rd_data7'] +  $rate_data[9]['rd_data7']
                        +  $rate_data[8]['rd_data7'] +  $rate_data[7]['rd_data7'] +  $rate_data[6]['rd_data7']
                        +  $rate_data[5]['rd_data7'] +  $rate_data[4]['rd_data7'] +  $rate_data[3]['rd_data7']
                        +  $rate_data[2]['rd_data7'] +  $rate_data[1]['rd_data7'] +  $rate_data[0]['rd_data7']  )}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  @if($rate_data_general['rdg_sum2'] && $rate_data_general['rdg_sum2'] > 0 )
  <div class="page-break"></div>
  <div>
    <table id="custom_table">
      <thead>
        <tr>
          <th colspan="9">보관료</th>
        </tr>
        <tr>
          <td colspan="2">항목</td>
          <td>단위</td>
          <td>단가</td>
          <td>수량</td>
          <td>공급가</td>
          <td>부가세</td>
          <td>급액</td>
          <td>비고</td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td rowspan="3" style='font-family: "unbatang", Times, serif'>
            보관
          </td>
          <td style='font-family: "unbatang", Times, serif'>
            기본료
          </td>
          <td>
            {{$rate_data[21]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[21]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[21]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[21]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[21]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[21]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[21]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            PCS
          </td>
          <td>
            {{$rate_data[22]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[22]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td style='font-family: "unbatang", Times, serif'>
            팔렛
          </td>
          <td>
            {{$rate_data[23]['rd_data1']}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data2'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data4'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data5'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data6'])}}
          </td>
          <td>
            {{number_format($rate_data[23]['rd_data7'])}}
          </td>
          <td>
            {{$rate_data[23]['rd_data8']}}
          </td>
        </tr>
        <tr>
          <td colspan="2" style='font-family: "unbatang", Times, serif'>
            합계
          </td>
          <td>
          </td>
          <td>
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data4'] + $rate_data[21]['rd_data4'] +  $rate_data[23]['rd_data4'] )}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data5'] + $rate_data[21]['rd_data5'] +  $rate_data[23]['rd_data5'] )}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data6'] + $rate_data[21]['rd_data6'] +  $rate_data[23]['rd_data6'] )}}
          </td>
          <td>
            {{number_format($rate_data[22]['rd_data7'] + $rate_data[21]['rd_data7'] +  $rate_data[23]['rd_data7'] )}}
          </td>
          <td>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
  @endif
  <div style="margin:10px 0px;">
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>1. 상세 업무 내역에 따라 예상비용은 변경될 수 있습니다.</p>
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>2. 본 내역은 실제 비용이 아님을 명시합니다.</p>
  </div>
  <div style="text-align:right">
    @if($co_name_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_name_send}}</p>
    @endif
    @if($co_address_send && $co_address_detail_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}} {{$co_address_detail_send}}</p>
    @elseif($co_address_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_address_send}}</p>
    @endif
    @if($co_tel_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_tel_send}}</p>
    @endif
    @if($co_email_send)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$co_email_send}}</p>
    @endif
    @if($date)
    <p style='font-size:12px;padding:0px;margin:0px;margin-top:5px;font-family: "unbatang", Times, serif'>{{$date}}</p>
    @endif
  </div>
  @endif

</body>

</html>