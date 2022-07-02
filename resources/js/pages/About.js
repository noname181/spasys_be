
import ReactDOM from 'react-dom';
import React, { useState, useEffect, useRef } from 'react';
import { Routes, Route, Link } from "react-router-dom";
import axios from 'axios';
import CreateExpense from './products/create';


export default function About() {
    return (
      <>
        <main>
          <h2>Who are we?</h2>
          <p>
            That feels like an existential question, don't you
            think?
          </p>
        </main>
        <CreateExpense></CreateExpense>
        <nav>
          <Link to="/">Home</Link>
        </nav>
      </>
    );
  }
