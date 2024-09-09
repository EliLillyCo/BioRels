#include "headers/molecule/hetentry.h"
#include "headers/parser/ofstream_utils.h"
#include "headers/molecule/macromole_utils.h"

protspace::HETEntry::HETEntry():
    mName(""),
    mReplaced(""),
    mResClass(0x0000),
    mTautomer(0),
    mDistMatrix(80,80),
    mMatrixDone(false)
{

}
void protspace::HETEntry::serialize(std::ofstream& ofs)
{

    saveSerializedString(ofs,mName);
    saveSerializedString(ofs,mReplaced);
    ofs.write((char*)&mResClass,sizeof(uint16_t));
    ofs.write((char*)&mTautomer,sizeof(unsigned char));
    ofs.write((char*)&mMatrixDone,sizeof(bool));
    mMolecule.serialize(ofs);
//    unsigned int size=mDistMatrix.numColumns();
//    ofs.write((char*)&size,sizeof(unsigned int));
//    size=mDistMatrix.numRows();
//    ofs.write((char*)&size,sizeof(unsigned int));
//    size_t sizeA = mDistMatrix.size();
//    ofs.write((char*)&sizeA,sizeof(size_t));
//    int val=0;
//    for(size_t i=0;i<sizeA;++i)
//    {
//        val=mDistMatrix.val(i);

//        ofs.write((char*)&val,sizeof(int));
//    }
//
}


void protspace::HETEntry::unserialize(std::ifstream& ifs,const bool& wMatrix)
{
    readSerializedString(ifs,mName);
    readSerializedString(ifs,mReplaced);
    ifs.read((char*)&mResClass,sizeof(uint16_t));

    ifs.read((char*)&mTautomer,sizeof(unsigned char));
    ifs.read((char*)&mMatrixDone,sizeof(bool));
    mMolecule.unserialize(ifs);


//    unsigned int sizeC,sizeR;    size_t size;
//    ifs.read((char*)&sizeC,sizeof(unsigned int));
//    ifs.read((char*)&sizeR,sizeof(unsigned int));
//    mDistMatrix.resize(sizeC,sizeR);
//    ifs.read((char*)&size,sizeof(size_t));
//    std::cout <<sizeC<<" " <<sizeR<<" " <<size<<std::endl;

//    int val=0;

//    for(size_t i=0;i<size;++i)
//    {

//        ifs.read((char*)&val,sizeof(int));
//        mDistMatrix.at(i)=val;
//    }
   if (wMatrix) calcDistMatrix();

}


void protspace::HETEntry::calcDistMatrix()
{
    protspace::getDistanceMatrix(mMolecule,mDistMatrix);
}
