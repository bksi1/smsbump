SMS App

Installation:

**Backend**
1. Copy base code in the target folder
2. Create empty database and populate correct db credentials in app/config/config.php
3. Run the following command in the terminal to create tables in the database

   ```bash sms_app command/init```

   ```sms_app.bat command/init``` for windows

4. Configure your web server to point to the public folder
5. Set cronjob to run the following command every minute

    ```bash sms_app command/send```

    ```sms_app.bat command/send``` for windows

provided API endpoints:

1. **POST /user/register**
    - Send SMS
    - Request form:
        ```
        "email": "aaa@bbb.ccc",
        "phone": "+65879425365"
        "password": "123456"
        ```
    - Response:
        ```json
        {
            "message": "User registered, please check your phone for OTP code", "userId": "{userId}"
        }
        ```
After this request, the app will send a verification code to the phone number provided.
The user will need to provide the verification code to complete the registration process.
1. **POST /user/validate/{id}**
    - Validate user by code sent to phone
    - Request form:
        ```
        "code": "569813"
        ```
    - Response:
        ```json
        {
            "message": "User validated successfully"
        }
        ```

1. **GET /user/generate/{id}**
    - Generates new OTP code
    - No request body required
    - Response:
        ```json
        {
            "message": "Code generated, please check your phone"
        }
        ```

**Frontend**

1. Go to the frontend/sms_app folder
2. Run the following command in the terminal to install the required packages

    ```bash npm install```

    ```npm install``` for windows

3. Navigate to the .env file and update the REACT_APP_API_URL variable to point to the backend server
4. Run the following command in the terminal to start the frontend server

    ```bash npm start```
    ```npm start``` for windows

5. Open the browser and navigate to the following URL

    ```http://localhost:3000```
6. If everything seems ok run the following command to build the frontend

    ```bash npm run build```

    ```npm run build``` for windows


There is a lot of space for improvement in the code, but I tried to keep it simple and clean and without using external php libraries. So the ORM prototype is far from ideal. Also there is no route validation. 
I hope you will like it. 
Thank you for your time and consideration.