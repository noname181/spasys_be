import React, { useEffect, useState } from "react";
import InfiniteScroll from "react-infinite-scroll-component";
import axios from "axios";

const style = {
    height: 200,
    border: "1px solid green",
    margin: 6,
    padding: 8,
};

function InfinityScroll() {
    const [expenses, setExpenses] = useState([]);
    const [nextPage, setNextPage] = useState("");

    //   state = {
    //     items: Array.from({ length: 20 })
    //   };

    useEffect(() => {
        axios.get("http://54.180.153.148/api/pagination").then((res) => {
            setExpenses(res.data.data);
            setNextPage(res.data.next_page_url);
        });
    }, []);

    useEffect(() => {
        console.log(expenses.data);
    }, [expenses]);

    const fetchMoreData = () => {
        // a fake async api call like which sends
        // 20 more records in 1.5 secs
        axios.get(nextPage).then((res) => {
            let temp = expenses.concat(res.data.data);
            setExpenses(temp);
            setNextPage(res.data.next_page_url);
        });
    };

    return (
        <div>
            <h1>demo: react-infinite-scroll-component</h1>
            <hr />
            {expenses.length > 0 && (
                <InfiniteScroll
                    dataLength={expenses.length}
                    next={fetchMoreData}
                    hasMore={true}
                    loader={<h4>Loading...</h4>}
                    pullDownToRefresh={true}
                    refreshFunction={() => {
                        axios
                            .get("http://54.180.153.148/api/pagination")
                            .then((res) => {
                                setExpenses(res.data.data);
                                setNextPage(res.data.next_page_url);
                            });
                    }}
                >
                    {expenses.map((i, index) => (
                        <div style={style} key={index}>
                            #{index} - {i.name}
                            <img
                                src={
                                    "http://localhost:8000/storage/product/image/" +
                                    i.image
                                }
                                style={{ width: "150px", height: "150px" }}
                            />
                        </div>
                    ))}
                </InfiniteScroll>
            )}
        </div>
    );
}

export default InfinityScroll;
