import React, { useState, useEffect } from "react";
import axios from 'axios';
import Table from 'react-bootstrap/Table';
import ExpenseTableRow from './tablerow';


export default function ExpenseList() {

    const [expenses, setExpenses] = useState([]);
    const [reload,setReload] = useState(false); 

    useEffect(() => {
        axios.get('http://54.180.153.148/api/expenses/')
      .then(res => {

        setExpenses(res.data)
  
      })
      .catch((error) => {
        console.log(error);
      })
    },[reload]);
    
    const deleted =()=> {
      setReload(!reload);
    }

  const DataTable =()=> {
    return expenses.map((res, i) => {
      return <ExpenseTableRow obj={res} key={i} onDelete={deleted} />;
    });
  }



    return (<div className="table-wrapper"> 
      <Table striped bordered hover>
        <thead>
          <tr>
            <th>Name</th>
            <th>Amount</th>
            <th>Description</th>
            <th>mage</th>
            <th>Actionnnnnnn</th>
          </tr>
        </thead>
        <tbody>
          {DataTable()}
        </tbody>
      </Table>
    </div>);
  
}