import React, {useState} from 'react';
export default function ValidateForm (
    {
        isActive,
        formState,
        onChange
    }
)
{
    let userId = formState.response ? formState.response.userId : null;
    const [code, setCode] = useState("");
    const [status, setStatus] = useState('typing');
    const [error, setError] = useState(null);
    const handleInputChange = (e) => {
        if (e.target.placeholder === 'Sms code') {
            setCode(e.target.value);
        }
    }
    const handleValidation = (e) => {
        e.preventDefault();
        setError(null);
        setStatus('submitting');
        if (code === "") {
            setError({message: "Code is required"});
            setStatus('error');
            return;
        }
        fetch(process.env.REACT_APP_API_URL+'user/validate/'+userId, {
            method: 'POST',
            body: JSON.stringify({code}),
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.status === 200) {
                return response.json()
            } else {
                throw new Error(response.json());
            }
        }).then(data => {
            if(typeof data.error != "undefined"){
                throw new Error(data.error);
            } else {
                setStatus('codeValidated');
                onChange({message:"isValidated", userId: userId});
            }
        }).catch(e => {
            setError({message: "Wrong code"});
            setStatus('error');
        });
    }

    const getRequestGenerateCode = (e) => {
        e.preventDefault();
        setError(null);
        setStatus('submitting');
        fetch(process.env.REACT_APP_API_URL+'user/generate/'+userId, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.status === 200) {
                return response.json()
            } else {
                throw new Error(response.json());
            }
        }).then(data => {
            if(typeof data.error != "undefined"){
                setStatus('codeGenerated');
                onChange({message:"isValidated", userId: userId});
            } else {
                throw new Error(data.error);
            }
        }).catch(e => {
            setError({message: "Please wait couple of minutes and try again"});
            setStatus('error');
        });
    }
  return (
      <div>
         {isActive &&
             <div>
                <h4>Validate Code</h4>
                 {status !== "codeValidated" &&
                     <div>
                         <p>Enter the code sent to your phone</p>
                         <form onSubmit={handleValidation}>
                             <div className="row">
                                 <div className="col-8">
                                     <input className="my-3 form-control" type="text" value={code}
                                            placeholder="Sms code"
                                            onChange={handleInputChange}/>
                                 </div>
                                 <div className="col-4">
                                     {status !== "submitting" &&
                                         <input type="submit" value="Submit" className="my-3 btn btn-primary"/>
                                     }
                                 </div>
                             </div>
                         </form>
                         {error !== null &&
                             <p className="alert alert-danger">
                                 {error.message}
                             </p>
                         }
                         <button className="btn btn-primary" onClick={getRequestGenerateCode}>Request new code</button>
                         {
                             status === "codeGenerated" &&
                             <p className="my-3 alert alert-primary">New code generated. Please check your phone</p>
                         }

                     </div>
                 }
                 {
                 status === "codeValidated" &&
                    <p className="alert alert-success">Code validated</p>
                }
             </div>
        }
      </div>
  );
}
