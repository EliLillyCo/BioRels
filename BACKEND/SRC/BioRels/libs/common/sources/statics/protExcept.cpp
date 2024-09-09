#include <sstream>
#include "headers/statics/protExcept.h"
#undef NDEBUG /// Active assertion in release

using namespace std;

short ProtExcept::verboseLevel=3;
ENFORCE ProtExcept::gEnforceRule=SHOW;
ProtExcept::ProtExcept(const std::string& id,
                       const std::string& location,
                       const std::string& description, const char *file, int line,
                       const unsigned short& threat)
    :mId(id),mLocation(location),mGlobalDescription(description),mThreat(threat)
{
    ostringstream oss; oss<< file<<"::" <<line<<" "<<mLocation;
    mHierarchy.push_back(oss.str());
}






std::string ProtExcept::toString() const
{
    ostringstream oss;
    if (verboseLevel==3)
    {

    oss<< "########## ERROR "<< mId << " ##########\n";
    oss<< "Location:   "<<mLocation<<"\n";
    oss <<"Error type: "<<mGlobalDescription<<"\n";

    oss<< "#####\nHierarchy:\n";
    size_t n=1;
    for (std::vector<std::string>::const_reverse_iterator
         it  = mHierarchy.rbegin();
         it != mHierarchy.rend();
       ++it)
    {
        for(size_t i=0;i<n;++i) oss<<"   ";
        oss << "|> "<< *it<< "\n";
        n++;
    }
    oss << "#####\nInformation:\n";
    for (std::vector<std::string>::const_reverse_iterator
         it  = mDescription.rbegin();
         it != mDescription.rend();
       ++it)
    {
        oss << *it << "\n";
    }
    }
    else if (verboseLevel==2)
    {
        oss<<"### ERROR:\t"<<mLocation<<"\t"<<mGlobalDescription<<"\n";
    }
    return oss.str();

}

