import './SmsApp.css';
import RegisterForm from './RegisterForm';
import ValidateForm from './ValidateForm';
import React from 'react';
import {useState} from "react";

export default function SmsApp() {
    const apiUrl = process.env.REACT_APP_API_URL;
    const [activeIndex, setActiveIndex] = useState({isRegistered: false});
    let handleStateChange = (state) => {
        console.log("Changing state to " + state);
    }
    return (
        <div className="SmsApp-header">
            <RegisterForm
                isActive={!activeIndex.isRegistered}

                onChange={
                    (changeMsg) => {
                        handleStateChange(changeMsg);
                        setActiveIndex({isRegistered: true, response: changeMsg});
                    }
                }
            />
            <ValidateForm
                isActive={activeIndex.isRegistered}
                formState={activeIndex}
                onChange={
                    (changeMsg) => {
                        handleStateChange(changeMsg);
                        setActiveIndex({isRegistered: true, isValidated: true, response: changeMsg});
                    }
                }
            />
        </div>
    );
};
