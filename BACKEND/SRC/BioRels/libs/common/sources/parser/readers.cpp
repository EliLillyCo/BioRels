#include <memory>

#include "headers/parser/readers.h"
#include "headers/parser/readPDB.h"
#include "headers/parser/readMOL2.h"
#include "headers/molecule/macromole.h"
#include "headers/parser/readSDF.h"
#include "headers/proc/multimole.h"
#include "headers/parser/writerMOL2.h"
#include "headers/parser/writerPDB.h"
#include "headers/parser/writerSDF.h"
#undef NDEBUG /// Active assertion in release
using namespace std;

namespace protspace
{

std::string getExtension(const std::string& pFile)
{
    const size_t pos = pFile.find_last_of(".");
    if( pos==string::npos)
        throw_line("440101",
                   "Readers::load",
                   "Unable to find extension");
    return pFile.substr(pos+1);
}

void readFile(GroupList<MacroMole>& list, const std::string& file)
{
assert(list.isOwner());
    ReaderBase* reader =nullptr;
    try{
        createReader(reader,file);
       while(!reader->isEOF())
       {
           protspace::MacroMole* mole=new protspace::MacroMole;
           list.add(mole);
           reader->load(*mole);

       }
        if (reader!= nullptr)delete reader;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("readFile");
        e.addDescription("File involved : "+file);
        if (reader!= nullptr)delete reader;
        throw;
    }
}
void readFile(MacroMole& mole, const std::string& file)
{

    ReaderBase* reader =nullptr;
    try{
        createReader(reader,file);
        reader->load(mole);
        if (reader!= nullptr)delete reader;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("readFile");
        e.addDescription("File involved : "+file);
        if (reader!= nullptr)delete reader;
        throw;
    }
}


void createReader(ReaderBase*& reader, const std::string& pFile)
{
    if (reader != nullptr)delete reader;
    const std::string ext(getExtension(pFile));
    if (ext=="mol2" || ext=="MOL2"){ reader=new ReadMOL2(pFile);}
    else if (ext=="pdb"|| ext=="PDB")reader=new ReadPDB(pFile);
    else if (ext=="sdf"|| ext=="SDF")reader=new ReadSDF(pFile);
    else throw_line("440301",
                    "Readers::createReader",
                    "Unrecognized extension "+ext);
}



void readMultiFile(MultiMole& multi, const std::string& pFile,const size_t& maxMole)
{
    size_t nDEVTESTCOUNT=0;
    ReaderBase* reader =nullptr;
    size_t nMole=0;
    try{
        createReader(reader,pFile);
        assert(reader != nullptr);
        while (!reader->isEOF())
        {
            MacroMole* mole = new MacroMole();
            try{
                reader->load(*mole);
                if (mole->numAtoms()==0)
                {
                    delete mole;continue;}

            }

            catch(ProtExcept &e)
            {
                e.addHierarchy("Readers::readMultiFile");
                std::cerr <<e.toString()<<std::endl;
                delete mole;
                continue;
            }

            multi.addStructure(*mole);
            ++nMole;
            if (nMole>=maxMole)break;
            nDEVTESTCOUNT++;
            //            if (nDEVTESTCOUNT==200)break;
        }
        if (reader!= nullptr)delete reader;

    }catch(ProtExcept &e)
    {
        e.addHierarchy("readMultiFile");
        e.addDescription("File involved : "+pFile);
        if (reader!= nullptr)delete reader;
        throw;
    }
}
std::ifstream::pos_type filesize(const std::string& filename)
{
    std::ifstream in(filename, std::ifstream::ate | std::ifstream::binary);
    if (!in.is_open())return 0;
    const std::ifstream::pos_type size(in.tellg());
    in.close();
    return size;
}

bool isInternal(const std::string& pFile)
{
    const size_t pos = pFile.find_last_of(".");
    if( pos==string::npos)
        throw_line("440401",
                   "Readers::isInternal",
                   "Unable to find extension");
    const size_t posT= pFile.find_last_of("/");
    std::string fileName="";
    if (posT==std::string::npos)fileName=pFile.substr(0,pos-1);
    else fileName=pFile.substr(posT+1,pos-posT-1);
    return(fileName.find_first_of("_")!=std::string::npos);
}


void createWriter(WriterBase*& writer, const std::string& pFile)
{
    if (writer != nullptr)delete writer;
    const std::string ext(getExtension(pFile));
    if (ext=="mol2" || ext=="MOL2"){ writer=new WriteMOL2(pFile);}
    else if (ext=="pdb"|| ext=="PDB")writer=new WritePDB(pFile);
    else if (ext=="sdf"|| ext=="SDF")writer=new WriterSDF(pFile);
    else throw_line("440501",
                    "Readers::createWriter",
                    "Unrecognized extension "+ext);
}

void saveFile(MacroMole& mole, const std::string& file,
              const bool& onlySelected)
{
    WriterBase* writer=nullptr;
    try{
        createWriter(writer,file);
        writer->onlySelected(onlySelected);
        writer->save(mole);
        if (writer!= nullptr)delete writer;
    }catch(ProtExcept &e)
    {
        e.addHierarchy("saveFile");
        e.addDescription("File involved : "+file);
        if (writer!= nullptr)delete writer;
        throw;
    }

}



void saveMultiFile(MultiMole& mole, const std::string& file,const bool& onlySelected)
{
    WriterBase* writer=nullptr;
    try{
        createWriter(writer,file);
        writer->onlySelected(onlySelected);
        for(size_t iM=0;iM<mole.size();++iM)
            writer->save(mole.getMole(iM));
        if (writer!= nullptr)delete writer;
    }catch(ProtExcept &e)
    {
        assert(e.getId()!="040101");
        e.addHierarchy("saveFile");
        e.addDescription("File involved : "+file);
        if (writer!= nullptr)delete writer;
        throw;
    }

}


}
