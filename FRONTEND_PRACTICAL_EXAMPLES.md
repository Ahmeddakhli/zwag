# Frontend Practical Examples - Matrimonial Platform

## 🔐 Complete Login Implementation

### React Login Component

```jsx
// components/LoginForm.jsx
import React, { useState } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useNavigate } from 'react-router-dom';

const LoginForm = () => {
  const [formData, setFormData] = useState({
    username: '',
    password: ''
  });
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);
  
  const { login } = useAuth();
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setErrors({});

    try {
      await login(formData.username, formData.password);
      navigate('/dashboard');
    } catch (error) {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        setErrors({ general: error.message || 'Login failed' });
      }
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  return (
    <div className="login-form">
      <form onSubmit={handleSubmit}>
        <div className="form-group">
          <input
            type="text"
            name="username"
            placeholder="Email or Mobile"
            value={formData.username}
            onChange={handleChange}
            required
          />
          {errors.username && (
            <span className="error">{errors.username[0]}</span>
          )}
        </div>

        <div className="form-group">
          <input
            type="password"
            name="password"
            placeholder="Password"
            value={formData.password}
            onChange={handleChange}
            required
          />
          {errors.password && (
            <span className="error">{errors.password[0]}</span>
          )}
        </div>

        {errors.general && (
          <div className="error general-error">{errors.general}</div>
        )}

        <button type="submit" disabled={loading}>
          {loading ? 'Signing in...' : 'Sign In'}
        </button>
      </form>
    </div>
  );
};

export default LoginForm;
```

### Vue.js Login Component

```vue
<!-- components/LoginForm.vue -->
<template>
  <div class="login-form">
    <form @submit.prevent="handleLogin">
      <div class="form-group">
        <input
          v-model="form.username"
          type="text"
          placeholder="Email or Mobile"
          required
        />
        <span v-if="errors.username" class="error">
          {{ errors.username[0] }}
        </span>
      </div>

      <div class="form-group">
        <input
          v-model="form.password"
          type="password"
          placeholder="Password"
          required
        />
        <span v-if="errors.password" class="error">
          {{ errors.password[0] }}
        </span>
      </div>

      <div v-if="generalError" class="error general-error">
        {{ generalError }}
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Signing in...' : 'Sign In' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const form = reactive({
  username: '',
  password: ''
});

const errors = ref({});
const generalError = ref('');
const loading = ref(false);

const { login } = useAuth();
const router = useRouter();

const handleLogin = async () => {
  loading.value = true;
  errors.value = {};
  generalError.value = '';

  try {
    await login(form);
    router.push('/dashboard');
  } catch (error) {
    if (error.errors) {
      errors.value = error.errors;
    } else {
      generalError.value = error.message || 'Login failed';
    }
  } finally {
    loading.value = false;
  }
};
</script>
```

## 👤 Profile Management Examples

### React Profile Upload Component

```jsx
// components/ProfileUpload.jsx
import React, { useState, useRef } from 'react';
import { profileService } from '../services/profileService';

const ProfileUpload = ({ currentImage, onImageUpdate }) => {
  const [uploading, setUploading] = useState(false);
  const [preview, setPreview] = useState(currentImage);
  const fileInputRef = useRef(null);

  const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
      // Validate file type and size
      if (!file.type.startsWith('image/')) {
        alert('Please select an image file');
        return;
      }

      if (file.size > 2 * 1024 * 1024) { // 2MB limit
        alert('File size must be less than 2MB');
        return;
      }

      // Show preview
      const reader = new FileReader();
      reader.onload = (e) => setPreview(e.target.result);
      reader.readAsDataURL(file);

      // Upload file
      uploadImage(file);
    }
  };

  const uploadImage = async (file) => {
    try {
      setUploading(true);
      const result = await profileService.updateAvatar(file);
      
      setPreview(result.data.avatar_url);
      onImageUpdate(result.data.avatar_url);
      
      alert('Profile image updated successfully!');
    } catch (error) {
      console.error('Upload failed:', error);
      alert('Failed to upload image. Please try again.');
      setPreview(currentImage); // Revert preview
    } finally {
      setUploading(false);
    }
  };

  const triggerFileSelect = () => {
    fileInputRef.current?.click();
  };

  return (
    <div className="profile-upload">
      <div className="image-container">
        <img 
          src={preview || '/default-avatar.png'} 
          alt="Profile" 
          className="profile-image"
        />
        {uploading && (
          <div className="upload-overlay">
            <div className="spinner">Uploading...</div>
          </div>
        )}
      </div>
      
      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        onChange={handleFileSelect}
        style={{ display: 'none' }}
      />
      
      <button 
        onClick={triggerFileSelect}
        disabled={uploading}
        className="upload-btn"
      >
        {uploading ? 'Uploading...' : 'Change Photo'}
      </button>
    </div>
  );
};

export default ProfileUpload;
```

### React Search with Filters

```jsx
// components/AdvancedSearch.jsx
import React, { useState, useEffect, useCallback } from 'react';
import { matchService } from '../services/matchService';
import { dataService } from '../services/dataService';
import debounce from 'lodash/debounce';

const AdvancedSearch = () => {
  const [matches, setMatches] = useState([]);
  const [filters, setFilters] = useState({
    age_min: '',
    age_max: '',
    height_min: '',
    height_max: '',
    religion: '',
    location: '',
    education: '',
    profession: ''
  });
  const [masterData, setMasterData] = useState({
    religions: [],
    educationLevels: [],
    professions: []
  });
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});

  // Load master data on component mount
  useEffect(() => {
    loadMasterData();
  }, []);

  // Debounced search function
  const debouncedSearch = useCallback(
    debounce(() => {
      searchMatches();
    }, 500),
    [filters]
  );

  // Trigger search when filters change
  useEffect(() => {
    debouncedSearch();
  }, [filters, debouncedSearch]);

  const loadMasterData = async () => {
    try {
      const [religions, educationLevels, professions] = await Promise.all([
        dataService.getReligions(),
        dataService.getEducationLevels(),
        dataService.getProfessionCategories()
      ]);

      setMasterData({
        religions: religions.data,
        educationLevels: educationLevels.data,
        professions: professions.data
      });
    } catch (error) {
      console.error('Failed to load master data:', error);
    }
  };

  const searchMatches = async (page = 1) => {
    try {
      setLoading(true);
      
      // Remove empty filters
      const cleanFilters = Object.entries(filters).reduce((acc, [key, value]) => {
        if (value && value.trim() !== '') {
          acc[key] = value;
        }
        return acc;
      }, {});

      const result = await matchService.searchMatches(cleanFilters, page);
      
      if (page === 1) {
        setMatches(result.data);
      } else {
        setMatches(prev => [...prev, ...result.data]);
      }
      
      setPagination({
        currentPage: result.current_page,
        lastPage: result.last_page,
        total: result.total,
        hasMore: result.current_page < result.last_page
      });
    } catch (error) {
      console.error('Search failed:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleFilterChange = (key, value) => {
    setFilters(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const clearFilters = () => {
    setFilters({
      age_min: '',
      age_max: '',
      height_min: '',
      height_max: '',
      religion: '',
      location: '',
      education: '',
      profession: ''
    });
  };

  const loadMore = () => {
    if (pagination.hasMore && !loading) {
      searchMatches(pagination.currentPage + 1);
    }
  };

  const sendInterest = async (userId) => {
    try {
      await matchService.sendInterest(userId);
      
      // Update the match in the list
      setMatches(prev => prev.map(match => 
        match.id === userId 
          ? { ...match, interest_sent: true }
          : match
      ));
      
      alert('Interest sent successfully!');
    } catch (error) {
      alert(error.message || 'Failed to send interest');
    }
  };

  const addToShortlist = async (userId) => {
    try {
      await matchService.addToShortlist(userId);
      
      setMatches(prev => prev.map(match => 
        match.id === userId 
          ? { ...match, is_shortlisted: true }
          : match
      ));
      
      alert('Added to shortlist!');
    } catch (error) {
      alert(error.message || 'Failed to add to shortlist');
    }
  };

  return (
    <div className="advanced-search">
      {/* Search Filters */}
      <div className="search-filters">
        <h3>Search Filters</h3>
        
        <div className="filter-row">
          <div className="filter-group">
            <label>Age Range</label>
            <div className="range-inputs">
              <input
                type="number"
                placeholder="Min"
                value={filters.age_min}
                onChange={(e) => handleFilterChange('age_min', e.target.value)}
                min="18"
                max="80"
              />
              <span>to</span>
              <input
                type="number"
                placeholder="Max"
                value={filters.age_max}
                onChange={(e) => handleFilterChange('age_max', e.target.value)}
                min="18"
                max="80"
              />
            </div>
          </div>

          <div className="filter-group">
            <label>Height Range (cm)</label>
            <div className="range-inputs">
              <input
                type="number"
                placeholder="Min"
                value={filters.height_min}
                onChange={(e) => handleFilterChange('height_min', e.target.value)}
                min="140"
                max="220"
              />
              <span>to</span>
              <input
                type="number"
                placeholder="Max"
                value={filters.height_max}
                onChange={(e) => handleFilterChange('height_max', e.target.value)}
                min="140"
                max="220"
              />
            </div>
          </div>
        </div>

        <div className="filter-row">
          <div className="filter-group">
            <label>Religion</label>
            <select
              value={filters.religion}
              onChange={(e) => handleFilterChange('religion', e.target.value)}
            >
              <option value="">All Religions</option>
              {masterData.religions.map(religion => (
                <option key={religion.id} value={religion.name}>
                  {religion.name}
                </option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <label>Location</label>
            <input
              type="text"
              placeholder="City, State, Country"
              value={filters.location}
              onChange={(e) => handleFilterChange('location', e.target.value)}
            />
          </div>
        </div>

        <div className="filter-row">
          <div className="filter-group">
            <label>Education</label>
            <select
              value={filters.education}
              onChange={(e) => handleFilterChange('education', e.target.value)}
            >
              <option value="">All Education Levels</option>
              {masterData.educationLevels.map(level => (
                <option key={level.id} value={level.name}>
                  {level.name}
                </option>
              ))}
            </select>
          </div>

          <div className="filter-group">
            <label>Profession</label>
            <select
              value={filters.profession}
              onChange={(e) => handleFilterChange('profession', e.target.value)}
            >
              <option value="">All Professions</option>
              {masterData.professions.map(profession => (
                <option key={profession.id} value={profession.name}>
                  {profession.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <div className="filter-actions">
          <button onClick={clearFilters} className="clear-btn">
            Clear Filters
          </button>
          <div className="results-count">
            {pagination.total ? `${pagination.total} matches found` : ''}
          </div>
        </div>
      </div>

      {/* Search Results */}
      <div className="search-results">
        {loading && matches.length === 0 ? (
          <div className="loading-state">
            <div className="spinner"></div>
            <p>Searching for matches...</p>
          </div>
        ) : (
          <>
            <div className="matches-grid">
              {matches.map(match => (
                <div key={match.id} className="match-card">
                  <div className="match-image">
                    <img 
                      src={match.image || '/default-avatar.png'} 
                      alt={`${match.firstname} ${match.lastname}`}
                      onError={(e) => {
                        e.target.src = '/default-avatar.png';
                      }}
                    />
                  </div>
                  
                  <div className="match-info">
                    <h3>{match.firstname} {match.lastname}</h3>
                    <p className="age-location">
                      {match.age} years • {match.city}, {match.state}
                    </p>
                    {match.basic_info && (
                      <p className="profession">
                        {match.basic_info.profession}
                      </p>
                    )}
                    {match.religion_info && (
                      <p className="religion">
                        {match.religion_info.religion}
                      </p>
                    )}
                  </div>

                  <div className="match-actions">
                    <button 
                      onClick={() => sendInterest(match.id)}
                      disabled={match.interest_sent}
                      className={`interest-btn ${match.interest_sent ? 'sent' : ''}`}
                    >
                      {match.interest_sent ? 'Interest Sent' : 'Send Interest'}
                    </button>
                    
                    <button 
                      onClick={() => addToShortlist(match.id)}
                      disabled={match.is_shortlisted}
                      className={`shortlist-btn ${match.is_shortlisted ? 'added' : ''}`}
                    >
                      {match.is_shortlisted ? 'Shortlisted' : 'Add to Shortlist'}
                    </button>
                  </div>
                </div>
              ))}
            </div>

            {/* Load More */}
            {pagination.hasMore && (
              <div className="load-more">
                <button 
                  onClick={loadMore} 
                  disabled={loading}
                  className="load-more-btn"
                >
                  {loading ? 'Loading...' : 'Load More Matches'}
                </button>
              </div>
            )}

            {matches.length === 0 && !loading && (
              <div className="no-results">
                <h3>No matches found</h3>
                <p>Try adjusting your search filters to find more matches.</p>
              </div>
            )}
          </>
        )}
      </div>
    </div>
  );
};

export default AdvancedSearch;
```

## 💬 Messaging Implementation

### React Chat Component

```jsx
// components/ChatWindow.jsx
import React, { useState, useEffect, useRef } from 'react';
import { messageService } from '../services/messageService';

const ChatWindow = ({ conversationId, otherUser }) => {
  const [messages, setMessages] = useState([]);
  const [newMessage, setNewMessage] = useState('');
  const [loading, setLoading] = useState(false);
  const [sending, setSending] = useState(false);
  const messagesEndRef = useRef(null);
  const chatContainerRef = useRef(null);

  useEffect(() => {
    if (conversationId) {
      loadMessages();
      markAsRead();
    }
  }, [conversationId]);

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  const loadMessages = async () => {
    try {
      setLoading(true);
      const result = await messageService.getMessages(conversationId);
      setMessages(result.data.messages.data);
    } catch (error) {
      console.error('Failed to load messages:', error);
    } finally {
      setLoading(false);
    }
  };

  const markAsRead = async () => {
    try {
      await messageService.markAsRead(conversationId);
    } catch (error) {
      console.error('Failed to mark as read:', error);
    }
  };

  const sendMessage = async (e) => {
    e.preventDefault();
    
    if (!newMessage.trim()) return;

    try {
      setSending(true);
      const result = await messageService.sendMessage(
        otherUser.id, 
        newMessage.trim(), 
        conversationId
      );
      
      setMessages(prev => [...prev, result.data.message]);
      setNewMessage('');
    } catch (error) {
      console.error('Failed to send message:', error);
      alert('Failed to send message. Please try again.');
    } finally {
      setSending(false);
    }
  };

  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  };

  const formatMessageTime = (timestamp) => {
    return new Date(timestamp).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit'
    });
  };

  if (loading) {
    return (
      <div className="chat-loading">
        <div className="spinner"></div>
        <p>Loading messages...</p>
      </div>
    );
  }

  return (
    <div className="chat-window">
      {/* Chat Header */}
      <div className="chat-header">
        <div className="user-info">
          <img 
            src={otherUser.image || '/default-avatar.png'} 
            alt={otherUser.firstname}
            className="user-avatar"
          />
          <div className="user-details">
            <h3>{otherUser.firstname} {otherUser.lastname}</h3>
            <span className="user-status">Online</span>
          </div>
        </div>
      </div>

      {/* Messages Container */}
      <div className="messages-container" ref={chatContainerRef}>
        <div className="messages-list">
          {messages.map(message => (
            <div 
              key={message.id} 
              className={`message ${message.sender_id === otherUser.id ? 'received' : 'sent'}`}
            >
              <div className="message-content">
                <p>{message.message}</p>
                {message.is_edited && (
                  <span className="edited-indicator">edited</span>
                )}
              </div>
              <div className="message-time">
                {formatMessageTime(message.created_at)}
              </div>
            </div>
          ))}
          <div ref={messagesEndRef} />
        </div>
      </div>

      {/* Message Input */}
      <form onSubmit={sendMessage} className="message-input-form">
        <div className="input-container">
          <input
            type="text"
            value={newMessage}
            onChange={(e) => setNewMessage(e.target.value)}
            placeholder="Type your message..."
            disabled={sending}
            maxLength={1000}
          />
          <button 
            type="submit" 
            disabled={sending || !newMessage.trim()}
            className="send-button"
          >
            {sending ? '...' : 'Send'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default ChatWindow;
```

## 📱 Mobile-Specific Examples (React Native)

### React Native Login Screen

```jsx
// screens/LoginScreen.jsx
import React, { useState } from 'react';
import {
  View,
  Text,
  TextInput,
  TouchableOpacity,
  StyleSheet,
  Alert,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform
} from 'react-native';
import { authService } from '../services/authService.native';

const LoginScreen = ({ navigation }) => {
  const [formData, setFormData] = useState({
    username: '',
    password: ''
  });
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const handleLogin = async () => {
    if (!formData.username || !formData.password) {
      Alert.alert('Error', 'Please fill in all fields');
      return;
    }

    try {
      setLoading(true);
      setErrors({});
      
      await authService.login(formData.username, formData.password);
      navigation.replace('Dashboard');
    } catch (error) {
      if (error.errors) {
        setErrors(error.errors);
      } else {
        Alert.alert('Login Failed', error.message || 'Please try again');
      }
    } finally {
      setLoading(false);
    }
  };

  return (
    <KeyboardAvoidingView 
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
    >
      <View style={styles.formContainer}>
        <Text style={styles.title}>Welcome Back</Text>
        
        <View style={styles.inputContainer}>
          <TextInput
            style={[styles.input, errors.username && styles.inputError]}
            placeholder="Email or Mobile"
            value={formData.username}
            onChangeText={(text) => setFormData({...formData, username: text})}
            autoCapitalize="none"
            keyboardType="email-address"
          />
          {errors.username && (
            <Text style={styles.errorText}>{errors.username[0]}</Text>
          )}
        </View>

        <View style={styles.inputContainer}>
          <TextInput
            style={[styles.input, errors.password && styles.inputError]}
            placeholder="Password"
            value={formData.password}
            onChangeText={(text) => setFormData({...formData, password: text})}
            secureTextEntry
          />
          {errors.password && (
            <Text style={styles.errorText}>{errors.password[0]}</Text>
          )}
        </View>

        <TouchableOpacity
          style={[styles.loginButton, loading && styles.loginButtonDisabled]}
          onPress={handleLogin}
          disabled={loading}
        >
          {loading ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text style={styles.loginButtonText}>Sign In</Text>
          )}
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.forgotPasswordButton}
          onPress={() => navigation.navigate('ForgotPassword')}
        >
          <Text style={styles.forgotPasswordText}>Forgot Password?</Text>
        </TouchableOpacity>

        <TouchableOpacity
          style={styles.registerButton}
          onPress={() => navigation.navigate('Register')}
        >
          <Text style={styles.registerText}>
            Don't have an account? Sign Up
          </Text>
        </TouchableOpacity>
      </View>
    </KeyboardAvoidingView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  formContainer: {
    flex: 1,
    justifyContent: 'center',
    paddingHorizontal: 20,
  },
  title: {
    fontSize: 28,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 40,
    color: '#333',
  },
  inputContainer: {
    marginBottom: 20,
  },
  input: {
    backgroundColor: '#fff',
    padding: 15,
    borderRadius: 10,
    fontSize: 16,
    borderWidth: 1,
    borderColor: '#ddd',
  },
  inputError: {
    borderColor: '#ff4444',
  },
  errorText: {
    color: '#ff4444',
    fontSize: 14,
    marginTop: 5,
    marginLeft: 5,
  },
  loginButton: {
    backgroundColor: '#007bff',
    padding: 15,
    borderRadius: 10,
    alignItems: 'center',
    marginTop: 20,
  },
  loginButtonDisabled: {
    backgroundColor: '#ccc',
  },
  loginButtonText: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  forgotPasswordButton: {
    alignItems: 'center',
    marginTop: 20,
  },
  forgotPasswordText: {
    color: '#007bff',
    fontSize: 16,
  },
  registerButton: {
    alignItems: 'center',
    marginTop: 30,
  },
  registerText: {
    color: '#666',
    fontSize: 16,
  },
});

export default LoginScreen;
```

This practical guide provides ready-to-use components and examples that you can directly implement in your frontend application. Each example includes proper error handling, loading states, and follows modern development practices.