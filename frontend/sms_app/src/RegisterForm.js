import React, {useState} from 'react';

export default function RegisterForm
    ({
        isActive,
       onChange
    })
{
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [password, setPassword] = useState("");
  const [status, setStatus] = useState('typing');
  const [viewError, setError] = useState(null);

  const handleSubmit = (e) => {
    e.preventDefault();
    setError(null);
    setStatus('submitting');
    if (email === "" || phone === "" || password === "") {
      setError({message: "All fields are required"});
      setStatus('error');
      return;
    }
    fetch(process.env.REACT_APP_API_URL+'user/register', {
      method: 'POST',
      body: JSON.stringify({email, phone, password}),
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
        setStatus('success');
        onChange({message:"isRegistered", userId: data.userId});
      }
    }).catch(e => {
        setError({message: "Bad request"});
        setStatus('error');
    });
  }

  function handleInputChange(e) {
    if (e.target.placeholder === 'Email') {
      setEmail(e.target.value);
    } else if (e.target.placeholder === 'Phone') {
      setPhone(e.target.value);
    } else if (e.target.placeholder === 'Password') {
      setPassword(e.target.value);
    }
  }

  return (
    <div>
      {isActive &&
          <div>
            <p>Register Form </p>
            {viewError !== null && viewError.message !== undefined &&
                <p className="alert alert-danger">
                  {viewError.message}
                </p>
            }
            <form onSubmit={handleSubmit}>
              <input
                  value={email}
                  onChange={handleInputChange}
                  type="text"
                  placeholder="Email"
                  className="my-3 form-control"
              />
              <input
                  value={phone}
                  onChange={handleInputChange}
                  type="text"
                  placeholder="Phone"
                  className="my-3 form-control"
              />
              <input
                  value={password}
                  onChange={handleInputChange}
                  type="text"
                  placeholder="Password"
                  className="my-3 form-control"
              />
              {status !== "submitting" &&
                  <button className="btn btn-primary">Register</button>
              }
            </form>
          </div>}
    </div>
  );
}
