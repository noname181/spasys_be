import React, { useState, useEffect } from "react";
import Form from 'react-bootstrap/Form'
import Button from 'react-bootstrap/Button';
import axios from 'axios';
import { useParams } from 'react-router-dom';

export default function EditExpense(props) {

    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [amount, setAmount] = useState('');
    const [image, setImage] = useState('');
    const [image2, setImage2] = useState('');
    const { id } = useParams();
    useEffect(() => {
        axios.get('http://localhost:8000/api/expenses/' + id)
        .then(res => {
         
            setName(res.data.name)
              setDescription(res.data.description)
                setAmount(res.data.amount)
                setImage(res.data.image)
     
        })
        .catch((error) => {
          console.log(error);
        })
    },[])
  
    const onChangeImage= (e) => {
      setImage2(e.target.files[0])
    }
    const onChangeExpenseName = (e) => {
        setName(e.target.value)
      }
      const onChangeExpenseAmount = (e) => {
        setAmount(e.target.value)
      }
      const onChangeExpenseDescription = (e) => {
        setDescription(e.target.value)
      }

  const onSubmit=(e) => {
    e.preventDefault()

    // const expenseObject = {
    //   name,
    //   amount,
    //   description
    // };
    const formData = new FormData();
    // Update the formData object
    if(image2){
    formData.append(
        "image",
        image2,
        image2.name
    );
    }
    formData.append("name", name);
    formData.append("amount", amount);
    formData.append("description", description);
    console.log(formData)

    axios.post('http://localhost:8000/api/expenses/' + id,formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
      .then((res) => {
        console.log(res.data)
        console.log('Expense successfully updated')
      }).catch((error) => {
        console.log(error)
      })

    // Redirect to Expense List 
    // this.props.history.push('/expenses-listing')
  }

  const fileData = () => {
    if (image) {
        return (
            <div>
               

                <img alt="not fount" width={"250px"} src={URL.createObjectURL(image)} />

                
            </div>
        );
    } else {
        return (
            <div>
                <br />
                <h4>Choose before Pressing the Upload button</h4>
            </div>
        );
    }
  };

    return (<div className="form-wrapper">
      <Form onSubmit={onSubmit}>
        <Form.Group controlId="Name">
          <Form.Label>Name</Form.Label>
          <Form.Control type="text" value={name} onChange={onChangeExpenseName} />
        </Form.Group>

        <Form.Group controlId="Amount">
          <Form.Label>Amount</Form.Label>
          <Form.Control type="number" value={amount} onChange={onChangeExpenseAmount} />
        </Form.Group>

        <Form.Group controlId="Description">
          <Form.Label>Description</Form.Label>
          <Form.Control type="text" value={description} onChange={onChangeExpenseDescription} />
        </Form.Group>
        <Form.Group controlId="Description">
          <Form.Label>Image</Form.Label>
          <Form.Control type="file"  onChange={onChangeImage}/>
         { image2 ?<img alt="not fount" width={"250px"} src={URL.createObjectURL(image2)} /> : <img src={"http://localhost:8000/storage/product/image/" + image}  style={{width: '50px', height: '50px'}}/>}
        </Form.Group>

        <Button variant="danger" size="lg" block="block" type="submit">
          Update Expense
        </Button>
      </Form>
    </div>);

}