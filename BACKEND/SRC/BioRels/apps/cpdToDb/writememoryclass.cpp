//
// Created by c188973 on 10/27/16.
//

#include <cstdlib>
#include <cstring>
#include <iostream>
#include "writememoryclass.h"

#define MAX_FILE_LENGTH 2000000

WriterMemoryClass::WriterMemoryClass()
{
    this->m_pBuffer = NULL;
    this->m_pBuffer = (char*) malloc(MAX_FILE_LENGTH * sizeof(char));
    this->m_Size = 0;
}

WriterMemoryClass::~WriterMemoryClass()
{
    if (this->m_pBuffer)
        free(this->m_pBuffer);
}


void* WriterMemoryClass::Realloc(void* ptr, size_t size)
{
    if(ptr)
        return realloc(ptr, size);
    else
        return malloc(size);
}

size_t WriterMemoryClass::WriteMemoryCallback(char* ptr, size_t size, size_t nmemb)
{
    // Calculate the real size of the incoming buffer
    size_t realsize = size * nmemb;

    // (Re)Allocate memory for the buffer
    m_pBuffer = (char*) Realloc(m_pBuffer, m_Size + realsize);

    // Test if Buffer is initialized correctly & copy memory
    if (m_pBuffer == NULL) {
        realsize = 0;
    }

    memcpy(&(m_pBuffer[m_Size]), ptr, realsize);
    m_Size += realsize;


    // return the real size of the buffer...
    return realsize;
}

void WriterMemoryClass::print()
{
    std::cout << "Size: " << m_Size << std::endl;
    std::cout << "Content: " << std::endl << m_pBuffer << std::endl;
}
