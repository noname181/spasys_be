import React, { Component } from "react";
import { Link } from "react-router-dom";
import Button from "react-bootstrap/Button";
import axios from "axios";

export default function ExpenseTableRow(props) {
    const deleteExpense = () => {
        axios
            .delete("http://localhost:8000/api/expenses/" + props.obj.id)
            .then((res) => {
                console.log("Expense removed deleted!");
                props.onDelete();
            })
            .catch((error) => {
                console.log(error);
            });
    };

    return (
        <tr>
            <td>{props.obj.name}</td>
            <td>{props.obj.amount}</td>
            <td>{props.obj.description}</td>
            <td>
                <img
                    src={
                        "http://localhost:8000/storage/product/image/" +
                        props.obj.image
                    }
                    style={{ width: "50px", height: "50px" }}
                />
            </td>
            <td>
                <Link
                    className="edit-link"
                    to={"/edit-expense/" + props.obj.id}
                >
                    <Button size="sm" variant="info">
                        Edit
                    </Button>
                </Link>
                <Button onClick={deleteExpense} size="sm" variant="danger">
                    Delete
                </Button>
            </td>
        </tr>
    );
}
