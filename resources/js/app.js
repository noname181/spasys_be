import { createRoot } from "react-dom/client";
import React, { useState } from "react";
import Nav from "react-bootstrap/Nav";
import Navbar from "react-bootstrap/Navbar";
import Container from "react-bootstrap/Container";
import Row from "react-bootstrap/Row";
import Col from "react-bootstrap/Col";
import "bootstrap/dist/css/bootstrap.css";

import { BrowserRouter as Router, Routes, Route, Link } from "react-router-dom";

import EditExpense from "./components/products/edit";
import ExpensesList from "./components/products/listing";
import CreateExpense from "./components/products/create";
import InfinityScroll from "./pages/InfinityScroll";
import ReactTable from "./pages/ReactTable";

export default function App() {
    return (
        <Router basename={"/"}>
            <div className="App">
                <header className="App-header">
                    <Navbar>
                        <Container>
                            <Navbar.Brand>
                                <Link
                                    to={"/create-expense"}
                                    className="nav-link"
                                >
                                    Expense manager
                                </Link>
                            </Navbar.Brand>

                            <Nav className="justify-content-end">
                                <Nav>
                                    <Link
                                        to={"/create-expense"}
                                        className="nav-link"
                                    >
                                        Create Expense
                                    </Link>
                                    <Link
                                        to={"/expenses-listing"}
                                        className="nav-link"
                                    >
                                        Expenses List
                                    </Link>
                                    <Link
                                        to={"/infinity-scroll"}
                                        className="nav-link"
                                    >
                                        Infinity Scroll
                                    </Link>
                                    <Link
                                        to={"/react-table"}
                                        className="nav-link"
                                    >
                                        React Table
                                    </Link>
                                </Nav>
                            </Nav>
                        </Container>
                    </Navbar>
                </header>

                <Container>
                    <Row>
                        <Col md={12}>
                            <div className="wrapper">
                                <Routes>
                                    <Route
                                        exact
                                        path="/public/"
                                        element={
                                            <CreateExpense></CreateExpense>
                                        }
                                    />
                                    <Route
                                        path="/create-expense"
                                        element={
                                            <CreateExpense></CreateExpense>
                                        }
                                    />
                                    <Route
                                        path="/edit-expense/:id"
                                        element={<EditExpense></EditExpense>}
                                    />
                                    <Route
                                        path="/expenses-listing"
                                        element={<ExpensesList></ExpensesList>}
                                    />
                                    <Route
                                        path="/infinity-scroll"
                                        element={
                                            <InfinityScroll></InfinityScroll>
                                        }
                                    />
                                    <Route
                                        path="/react-table"
                                        element={<ReactTable></ReactTable>}
                                    />
                                </Routes>
                            </div>
                        </Col>
                    </Row>
                </Container>
            </div>
        </Router>
    );
}

const container = document.getElementById("app");
const root = createRoot(container); // createRoot(container!) if you use TypeScript
root.render(<App tab="home" />);
