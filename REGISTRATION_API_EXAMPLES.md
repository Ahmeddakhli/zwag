# Registration API - Usage Examples

## Overview

This document provides practical examples for using the Registration API endpoints. All examples include real HTTP requests and responses.

## Base URL
```
https://your-domain.com/api/v1
```

## Authentication
- Registration endpoints are **public** (no authentication required)
- Successful registration returns an authentication token

---

## 📋 1. Get Registration Configuration

**Endpoint:** `GET /auth/registration-config`

Get system registration settings and requirements before showing the registration form.

### Request
```javascript
fetch('https://your-domain.com/api/v1/auth/registration-config', {
  method: 'GET',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
})
```

### Response
```json
{
  "success": true,
  "data": {
    "registration_enabled": true,
    "email_verification_required": true,
    "mobile_verification_required": false,
    "kyc_verification_required": true,
    "agreement_required": true,
    "captcha_required": false,
    "secure_password_required": true,
    "default_package": {
      "id": 1,
      "name": "Free Package",
      "price": 0,
      "validity_period": 30,
      "interest_express_limit": 10,
      "contact_view_limit": 5,
      "image_upload_limit": 3
    },
    "password_requirements": {
      "min_length": 6,
      "mixed_case": true,
      "numbers": true,
      "symbols": true,
      "uncompromised": true
    }
  }
}
```

### Frontend Usage
```javascript
// Check if registration is enabled
const config = await getRegistrationConfig();
if (!config.data.registration_enabled) {
  showError('Registration is currently disabled');
  return;
}

// Show/hide form fields based on requirements
if (config.data.agreement_required) {
  showAgreementCheckbox();
}

if (config.data.captcha_required) {
  loadCaptcha();
}
```

---

## 🔍 2. Check User Existence

**Endpoint:** `POST /auth/check-user`

Check if email, mobile, or username is already registered.

### Check Email
```javascript
fetch('https://your-domain.com/api/v1/auth/check-user', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    email: 'john.doe@example.com'
  })
})
```

**Response:**
```json
{
  "success": true,
  "data": {
    "exists": false,
    "type": "email",
    "field": "Email",
    "message": "Email is available"
  }
}
```

### Check Mobile Number
```javascript
fetch('https://your-domain.com/api/v1/auth/check-user', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    mobile: '1234567890',
    mobile_code: '+1'
  })
})
```

**Response:**
```json
{
  "success": true,
  "data": {
    "exists": true,
    "type": "mobile",
    "field": "Mobile",
    "message": "Mobile number is already registered"
  }
}
```

### Frontend Usage
```javascript
// Real-time email validation
const checkEmail = async (email) => {
  try {
    const response = await fetch('/api/v1/auth/check-user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    
    const result = await response.json();
    
    if (result.data.exists) {
      showError('emailField', 'This email is already registered');
      return false;
    } else {
      showSuccess('emailField', 'Email is available');
      return true;
    }
  } catch (error) {
    showError('emailField', 'Unable to verify email');
    return false;
  }
};

// Use with debouncing for better UX
const debouncedEmailCheck = debounce(checkEmail, 500);
```

---

## 🔐 3. Check Password Strength

**Endpoint:** `POST /auth/check-password-strength`

Validate password strength and get improvement suggestions.

### Request
```javascript
fetch('https://your-domain.com/api/v1/auth/check-password-strength', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    password: 'MyPassword123!'
  })
})
```

### Response
```json
{
  "success": true,
  "data": {
    "score": 100,
    "feedback": [],
    "requirements": {
      "min_length": true,
      "lowercase": true,
      "uppercase": true,
      "numbers": true,
      "symbols": true
    },
    "level": "strong"
  }
}
```

### Weak Password Example
```json
{
  "success": true,
  "data": {
    "score": 20,
    "feedback": [
      "Password must contain uppercase letters",
      "Password must contain numbers",
      "Password must contain special characters"
    ],
    "requirements": {
      "min_length": true,
      "lowercase": true,
      "uppercase": false,
      "numbers": false,
      "symbols": false
    },
    "level": "weak"
  }
}
```

### Frontend Usage
```javascript
const checkPasswordStrength = async (password) => {
  const response = await fetch('/api/v1/auth/check-password-strength', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ password })
  });
  
  const result = await response.json();
  const { score, level, feedback, requirements } = result.data;
  
  // Update password strength indicator
  updatePasswordStrengthBar(score);
  updatePasswordLevel(level);
  
  // Show feedback
  if (feedback.length > 0) {
    showPasswordFeedback(feedback);
  }
  
  // Update requirement checklist
  updateRequirements(requirements);
  
  return level === 'strong' || level === 'good';
};
```

---

## 👤 4. User Registration

**Endpoint:** `POST /auth/register`

Register a new user account.

### Basic Registration Request
```javascript
fetch('https://your-domain.com/api/v1/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    firstname: 'John',
    lastname: 'Doe',
    email: 'john.doe@example.com',
    password: 'SecurePassword123!',
    password_confirmation: 'SecurePassword123!',
    agree: true
  })
})
```

### Complete Registration Request
```javascript
fetch('https://your-domain.com/api/v1/auth/register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify({
    firstname: 'John',
    lastname: 'Doe',
    email: 'john.doe@example.com',
    mobile: '1234567890',
    dial_code: '+1',
    username: 'johndoe123',
    password: 'SecurePassword123!',
    password_confirmation: 'SecurePassword123!',
    country: 'United States',
    country_code: 'US',
    state: 'California',
    city: 'Los Angeles',
    zip: '90210',
    address: '123 Main Street',
    agree: true,
    captcha: 'abc123' // if captcha is required
  })
})
```

### Success Response
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "profile_id": "12345678",
      "firstname": "John",
      "lastname": "Doe",
      "email": "john.doe@example.com",
      "mobile": "1234567890",
      "dial_code": "+1",
      "username": "johndoe123",
      "image": null,
      "status": 1,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "basic_info": null,
      "religion_info": null,
      "package_info": {
        "id": 1,
        "user_id": 1,
        "package_id": 1,
        "interest_express_limit": 10,
        "contact_view_limit": 5,
        "image_upload_limit": 3,
        "validity_period": 30,
        "expire_date": "2024-02-14T10:30:00.000000Z"
      },
      "verification_status": {
        "email_verified": false,
        "mobile_verified": true,
        "kyc_verified": false
      }
    },
    "token": "1|abc123def456...",
    "token_type": "Bearer"
  }
}
```

### Validation Error Response
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "firstname": ["The first name field is required"],
    "email": ["The email field is required"],
    "password": ["The password field is required"]
  }
}
```

### Duplicate Email Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["This email is already registered"]
  }
}
```

---

## 💻 Frontend Implementation Examples

### React Registration Form

```jsx
import { useState } from 'react';

const RegistrationForm = () => {
  const [formData, setFormData] = useState({
    firstname: '',
    lastname: '',
    email: '',
    password: '',
    password_confirmation: '',
    agree: false
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      const response = await fetch('/api/v1/auth/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const result = await response.json();

      if (result.success) {
        // Store token
        localStorage.setItem('auth_token', result.data.token);
        
        // Redirect to dashboard
        window.location.href = '/dashboard';
      } else {
        setErrors(result.errors || {});
      }
    } catch (error) {
      setErrors({ general: ['Registration failed. Please try again.'] });
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        type="text"
        name="firstname"
        placeholder="First Name"
        value={formData.firstname}
        onChange={(e) => setFormData({...formData, firstname: e.target.value})}
        required
      />
      {errors.firstname && <span className="error">{errors.firstname[0]}</span>}

      <input
        type="text"
        name="lastname"
        placeholder="Last Name"
        value={formData.lastname}
        onChange={(e) => setFormData({...formData, lastname: e.target.value})}
        required
      />
      {errors.lastname && <span className="error">{errors.lastname[0]}</span>}

      <input
        type="email"
        name="email"
        placeholder="Email"
        value={formData.email}
        onChange={(e) => setFormData({...formData, email: e.target.value})}
        required
      />
      {errors.email && <span className="error">{errors.email[0]}</span>}

      <input
        type="password"
        name="password"
        placeholder="Password"
        value={formData.password}
        onChange={(e) => setFormData({...formData, password: e.target.value})}
        required
      />
      {errors.password && <span className="error">{errors.password[0]}</span>}

      <input
        type="password"
        name="password_confirmation"
        placeholder="Confirm Password"
        value={formData.password_confirmation}
        onChange={(e) => setFormData({...formData, password_confirmation: e.target.value})}
        required
      />

      <label>
        <input
          type="checkbox"
          checked={formData.agree}
          onChange={(e) => setFormData({...formData, agree: e.target.checked})}
          required
        />
        I agree to the terms and conditions
      </label>
      {errors.agree && <span className="error">{errors.agree[0]}</span>}

      <button type="submit" disabled={loading}>
        {loading ? 'Creating Account...' : 'Create Account'}
      </button>

      {errors.general && (
        <div className="error">{errors.general[0]}</div>
      )}
    </form>
  );
};
```

### Vue.js Registration

```vue
<template>
  <form @submit.prevent="handleRegister">
    <div class="form-group">
      <input
        v-model="form.firstname"
        type="text"
        placeholder="First Name"
        required
      />
      <span v-if="errors.firstname" class="error">
        {{ errors.firstname[0] }}
      </span>
    </div>

    <div class="form-group">
      <input
        v-model="form.email"
        type="email"
        placeholder="Email"
        @blur="checkEmailAvailability"
        required
      />
      <span v-if="errors.email" class="error">
        {{ errors.email[0] }}
      </span>
    </div>

    <div class="form-group">
      <input
        v-model="form.password"
        type="password"
        placeholder="Password"
        @input="checkPasswordStrength"
        required
      />
      <div class="password-strength">
        <div :class="['strength-bar', passwordStrength.level]"></div>
        <span>{{ passwordStrength.level }}</span>
      </div>
    </div>

    <button type="submit" :disabled="loading">
      {{ loading ? 'Creating Account...' : 'Create Account' }}
    </button>
  </form>
</template>

<script setup>
import { reactive, ref } from 'vue'

const form = reactive({
  firstname: '',
  lastname: '',
  email: '',
  password: '',
  password_confirmation: '',
  agree: false
})

const errors = ref({})
const loading = ref(false)
const passwordStrength = ref({ level: 'weak', score: 0 })

const checkEmailAvailability = async () => {
  if (!form.email) return
  
  try {
    const response = await fetch('/api/v1/auth/check-user', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: form.email })
    })
    
    const result = await response.json()
    
    if (result.data.exists) {
      errors.value.email = ['This email is already registered']
    } else {
      delete errors.value.email
    }
  } catch (error) {
    console.error('Email check failed:', error)
  }
}

const checkPasswordStrength = async () => {
  if (!form.password) return
  
  try {
    const response = await fetch('/api/v1/auth/check-password-strength', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ password: form.password })
    })
    
    const result = await response.json()
    passwordStrength.value = result.data
  } catch (error) {
    console.error('Password strength check failed:', error)
  }
}

const handleRegister = async () => {
  loading.value = true
  errors.value = {}

  try {
    const response = await fetch('/api/v1/auth/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(form)
    })

    const result = await response.json()

    if (result.success) {
      localStorage.setItem('auth_token', result.data.token)
      window.location.href = '/dashboard'
    } else {
      errors.value = result.errors || {}
    }
  } catch (error) {
    errors.value.general = ['Registration failed. Please try again.']
  } finally {
    loading.value = false
  }
}
</script>
```

### Mobile App (React Native)

```javascript
import AsyncStorage from '@react-native-async-storage/async-storage';

const registerUser = async (userData) => {
  try {
    const response = await fetch('https://your-domain.com/api/v1/auth/register', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(userData)
    });

    const result = await response.json();

    if (result.success) {
      // Store token in AsyncStorage
      await AsyncStorage.setItem('auth_token', result.data.token);
      await AsyncStorage.setItem('user_data', JSON.stringify(result.data.user));
      
      return result.data;
    } else {
      throw new Error(result.message || 'Registration failed');
    }
  } catch (error) {
    throw error;
  }
};

// Usage in component
const handleRegister = async () => {
  try {
    const userData = {
      firstname: firstName,
      lastname: lastName,
      email: email,
      password: password,
      password_confirmation: confirmPassword,
      agree: true
    };
    
    const result = await registerUser(userData);
    
    // Navigate to main app
    navigation.replace('MainApp');
  } catch (error) {
    Alert.alert('Registration Failed', error.message);
  }
};
```

---

## 🔒 Security Best Practices

### 1. Input Validation
```javascript
// Always validate on frontend AND backend
const validateEmail = (email) => {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
};

const validatePassword = (password) => {
  return password.length >= 6 && 
         /[A-Z]/.test(password) && 
         /[a-z]/.test(password) && 
         /\d/.test(password);
};
```

### 2. Password Confirmation
```javascript
const validatePasswordMatch = (password, confirmation) => {
  return password === confirmation;
};
```

### 3. Rate Limiting
```javascript
// Implement client-side rate limiting for API calls
const rateLimitedEmailCheck = rateLimitFunction(checkEmail, 1000); // 1 call per second
```

### 4. Error Handling
```javascript
const handleApiError = (error) => {
  if (error.response?.status === 422) {
    // Validation errors
    return error.response.data.errors;
  } else if (error.response?.status === 403) {
    // Registration disabled
    return { general: ['Registration is currently disabled'] };
  } else {
    // Generic error
    return { general: ['Something went wrong. Please try again.'] };
  }
};
```

This documentation provides complete examples for integrating with the Registration API. Use these examples as a starting point for your frontend implementation.