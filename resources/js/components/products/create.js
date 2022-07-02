import React, { useState } from "react";
import Form from "react-bootstrap/Form";
import Button from "react-bootstrap/Button";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import axios from "axios";
import ExpensesList from "./listing";
import Swal from "sweetalert2";

export default function CreateExpense() {
    const [name, setName] = useState("");
    const [description, setDescription] = useState("");
    const [amount, setAmount] = useState("");
    const [image, setImage] = useState("");

    const onChangeExpenseName = (e) => {
        setName(e.target.value);
    };
    const onChangeExpenseAmount = (e) => {
        setAmount(e.target.value);
    };
    const onChangeExpenseDescription = (e) => {
        setDescription(e.target.value);
    };
    const onChangeExpenseImage = (e) => {
        setImage(e.target.files[0]);
        console.log(e.target.files[0])
    };

    const onSubmit = (e) => {
        e.preventDefault();
        // Create an object of formData
        const formData = new FormData();
        // Update the formData object
        formData.append(
            "image",
            image,
            image.name
        );
        formData.append("name", name);
        formData.append("amount", amount);
        formData.append("description", description);
        console.log(formData)
        axios
            .post("http://homestead.test/api/expenses/", formData, {
              headers: {
                'Content-Type': 'multipart/form-data'
              }
            })
            .then((res) => console.log(res.data));
        // console.log(`Expense successfully created!`);
        // console.log(`Name: ${this.state.name}`);
        // console.log(`Amount: ${this.state.amount}`);
        // console.log(`Description: ${this.state.description}`);
        Swal.fire("Good job!", "Expense Added Successfully", "success");

        setName("");
        setDescription("");
        setAmount("");

    };

    // File content to be displayed after
    // file upload is complete
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

    return (
        <div className="form-wrapper">
            <Form onSubmit={onSubmit}>
                <Row>
                    <Col>
                        <Form.Group controlId="Name">
                            <Form.Label>Name</Form.Label>
                            <Form.Control
                                type="text"
                                value={name}
                                onChange={onChangeExpenseName}
                            />
                        </Form.Group>
                    </Col>

                    <Col>
                        <Form.Group controlId="Amount">
                            <Form.Label>Amount</Form.Label>
                            <Form.Control
                                type="number"
                                value={amount}
                                onChange={onChangeExpenseAmount}
                            />
                        </Form.Group>
                    </Col>
                </Row>

                <Form.Group controlId="description">
                    <Form.Label>Description</Form.Label>
                    <Form.Control
                        as="textarea"
                        type="textarea"
                        value={description}
                        onChange={onChangeExpenseDescription}
                    />
                </Form.Group>

                <Form.Group controlId="Name">
                    <Form.Label>Name</Form.Label>
                    <Form.Control type="file" onChange={onChangeExpenseImage} />
                </Form.Group>
                {fileData()}
                <Button variant="primary" size="lg" block="block" type="submit">
                    Add Expense
                </Button>
            </Form>
            <br></br>
            <br></br>

            <ExpensesList> </ExpensesList>
        </div>
    );
}
