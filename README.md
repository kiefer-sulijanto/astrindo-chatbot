# Astrindo Digital Approval Chatbot  
**AI Driven Internal Business Intelligence System**

---

## Project Overview

The **Astrindo Digital Approval Chatbot** is an end-to-end AI-powered chatbot system designed to support **internal business operations** across **Marketing, HR, Finance, and Service** departments.

The system enables users to interact with structured enterprise data using **natural language queries**, eliminating the need for manual SQL queries or static dashboards. By integrating **OpenAI’s large language models** with a **MySQL-backed business database**, the chatbot delivers intelligent, context-aware responses tailored to real operational use cases.

This project is developed as an **internal enterprise system prototype**, demonstrating applied conversational AI, backend system design, and business process automation.

---

## Project Objectives

- **Natural Language Business Intelligence**  
  Allow non-technical users to query business data using plain English.

- **NLU-Based Intent Detection**  
  Use AI-driven intent classification instead of rigid keyword matching.

- **Modular Enterprise Architecture**  
  Design a scalable system where each business domain is isolated and extensible.

- **Context-Aware Conversations**  
  Support follow-up questions through short-term conversational memory.

- **Production-Ready Backend Design**  
  Implement clean PHP architecture with proper configuration and error handling.

---

## System & Architecture Overview

The chatbot follows a multi-stage **Natural Language Understanding (NLU) pipeline**, inspired by modern enterprise conversational systems.

### High-Level Workflow

1. **User Input**  
   User submits a free-form natural language query.

2. **Intent Understanding (NLU)**  
   OpenAI API analyzes the query to detect intent and extract key entities.

3. **Feature Routing**  
   The request is routed to the appropriate domain module (Marketing, HR, Finance, or Service).

4. **Database Interaction**  
   Relevant SQL queries are executed against the MySQL database.

5. **AI Response Generation**  
   Retrieved data is transformed into a clear, structured, and human-readable response.

6. **Context Storage**  
   Key intent and entities are stored to support follow-up questions.

---

## Core Features

- AI-powered conversational interface  
- NLU-based intent classification  
- Domain-specific business logic modules  
- MySQL-driven data retrieval  
- Context-aware follow-up handling  
- Automatic chat title generation  
- JSON-based API responses  
- Environment-secured configuration  

---

## Modular Architecture Design

The system is organized using a **domain-driven modular architecture**, ensuring scalability and maintainability.

```plaintext
astrindo-chatbot/
│
├── chat.php              # Main chatbot entry point
├── nlu.php               # Intent detection & routing
├── db.php                # Database connection
│
├── config/
│   └── env.php           # Environment variables (API keys)
│
├── features/
│   ├── marketing/        # Marketing analysis & cost tracking
│   ├── hr/               # HR-related queries & approvals
│   ├── finance/          # Financial summaries & reports
│   └── service/          # Service & operational support
│
├── logs/                 # Error and execution logs
└── README.md

```

---

## Technology Stack

| Layer | Technology |
|------|-----------|
| Backend | PHP (Native) |
| Database | MySQL |
| AI / NLU | OpenAI API |
| Server | Apache (XAMPP / Laragon) |
| Architecture | Modular NLU-Based Design |
| Output | Structured Text & JSON Responses |

---

## NLU & Intent Handling Strategy

Unlike traditional rule-based chatbots that rely on keyword matching, this system adopts an **AI-driven Natural Language Understanding (NLU)** approach to interpret user intent and context.

Key characteristics of the intent handling strategy include:
- Large Language Model (LLM)-based intent classification  
- Dynamic entity extraction (e.g. date, department, cost, personnel)  
- Context preservation across multiple conversational turns  
- Flexible handling of ambiguous or incomplete queries  

This enables natural interactions such as follow-up questions without requiring users to repeat information.

---

## Setup and Installation

### Prerequisites

- PHP 8.0 or higher  
- MySQL Database  
- Apache Server (XAMPP or Laragon)  
- OpenAI API Key  

### Installation Steps

1. Clone the repository:
```bash
git clone https://github.com/kiefer-sulijanto/astrindo-chatbot.git

