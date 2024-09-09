//
// Created by c188973 on 10/27/16.
//

#ifndef GC3TK_CPP_WRITEMEMORYCLASS_H
#define GC3TK_CPP_WRITEMEMORYCLASS_H

#include <cstddef>

class WriterMemoryClass
{
public:
    // Helper Class for reading result from remote host
    WriterMemoryClass();

    ~WriterMemoryClass();


    void* Realloc(void* ptr, size_t size);

    // Callback must be declared static, otherwise it won't link...
    size_t WriteMemoryCallback(char* ptr, size_t size, size_t nmemb);


    void print();

    // Public member vars
    char* m_pBuffer;
    size_t m_Size;
};

#endif //GC3TK_CPP_WRITEMEMORYCLASS_H
